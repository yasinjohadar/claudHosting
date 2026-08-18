<?php

namespace App\Console\Commands;

use App\Models\WhmAccount;
use App\Services\Whm\MailDns\WhmAccountLocator;
use App\Services\Whm\MailDns\WhmMailDnsSyncService;
use Illuminate\Console\Command;

/**
 * One command to install a cPanel account's mail DNS into Cloudflare.
 *
 * Defaults to the safe side: without --yes it asks, and --no-interaction without --yes
 * refuses rather than writing to live DNS unattended.
 */
class SyncMailDnsCommand extends Command
{
    protected $signature = 'whm:sync-mail-dns
                            {account? : معرّف الحساب أو اسم المستخدم أو النطاق}
                            {--domain= : نطاق محدّد (الافتراضي: نطاق الحساب الرئيسي)}
                            {--all : كل الحسابات النشطة والموقوفة}
                            {--dry-run : معاينة فقط بلا أي كتابة}
                            {--yes : تطبيق بلا سؤال}
                            {--ack=* : الإقرار بتحذير (مثل dmarc_generated)}
                            {--json : خرج JSON للأتمتة}';

    protected $description = 'تركيب سجلات البريد (MX / SPF / DKIM / DMARC) من منطقة cPanel إلى Cloudflare';

    public function handle(WhmMailDnsSyncService $sync, WhmAccountLocator $locator): int
    {
        $reference = (string) ($this->argument('account') ?? '');
        $all = (bool) $this->option('all');

        if ($reference === '' && ! $all) {
            $this->warn('حدّد حساباً أو استخدم --all');
            $this->line('مثال: php artisan whm:sync-mail-dns docs.claudsoft.com --dry-run');
            $this->line('مثال: php artisan whm:sync-mail-dns 44 --yes --ack=dmarc_generated');

            return self::INVALID;
        }

        $dryRun = (bool) $this->option('dry-run');

        // Writing to live DNS unattended needs an explicit --yes.
        if (! $dryRun && ! $this->option('yes') && ! $this->input->isInteractive()) {
            $this->error('التشغيل غير التفاعلي يتطلب --yes (أو استخدم --dry-run)');

            return self::INVALID;
        }

        $accounts = $all ? $locator->syncable()->all() : array_filter([$locator->find($reference)]);

        if ($accounts === []) {
            $this->error($all ? 'لا حسابات قابلة للمزامنة' : 'لم يُعثر على حساب: '.$reference);

            return self::INVALID;
        }

        $payloads = [];
        $failed = false;

        foreach ($accounts as $account) {
            $result = $this->runOne($sync, $account, $dryRun, count($accounts) > 1);
            $payloads[] = $result;

            if (! ($result['ok'] ?? false)) {
                $failed = true;
            }
        }

        if ($this->option('json')) {
            $this->line((string) json_encode(
                count($payloads) === 1 ? $payloads[0] : $payloads,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
            ));
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    protected function runOne(WhmMailDnsSyncService $sync, WhmAccount $account, bool $dryRun, bool $batch): array
    {
        $quiet = (bool) $this->option('json');
        $domain = (string) ($this->option('domain') ?: $account->domain);

        if (! $quiet) {
            $this->newLine();
            $this->info('→ '.$domain.' ('.$account->username.')');
        }

        $preview = $sync->preview($account, $domain, fresh: true);

        if (! $quiet) {
            $this->renderPreview($preview);
        }

        if ($preview['blockers'] !== []) {
            return $this->summarise($preview, 'blocked', false);
        }

        if (! ($preview['can_apply'] ?? false)) {
            return $this->summarise($preview, 'unchanged', true);
        }

        $acks = array_values(array_unique(array_merge(
            (array) $this->option('ack'),
            // In a batch run the operator cannot answer per account, so --yes carries
            // acknowledgement of the warnings that were just printed.
            $this->option('yes') ? array_column($preview['warnings'], 'key') : []
        )));

        if ($dryRun) {
            $result = $sync->apply($account, $domain, $preview['plan_hash'], $acks, dryRun: true, source: 'command');
            if (! $quiet) {
                $this->warn('  معاينة فقط — لم يُكتب شيء');
            }

            return $this->summarise($result, $result['outcome'] ?? 'dry_run', true);
        }

        if (! $this->option('yes')) {
            $label = $batch ? 'تطبيق التغييرات على '.$domain.'؟' : 'تطبيق التغييرات على Cloudflare؟';
            if (! $this->confirm($label, false)) {
                $this->line('  تم الإلغاء');

                return $this->summarise($preview, 'cancelled', true);
            }

            $acks = array_values(array_unique(array_merge($acks, array_column($preview['warnings'], 'key'))));
        }

        $result = $sync->apply($account, $domain, $preview['plan_hash'], $acks, dryRun: false, source: 'command');

        if (! $quiet) {
            $this->renderResults($result);
        }

        return $this->summarise($result, $result['outcome'] ?? 'failed', (bool) ($result['ok'] ?? false));
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    protected function renderPreview(array $preview): void
    {
        $zone = $preview['zone']['name'] ?? null;
        $this->line('  المنطقة على Cloudflare: '.($zone ?: '— غير موجودة'));

        foreach ($preview['plan']['notes'] ?? [] as $note) {
            $this->line('  · '.$note);
        }

        foreach ($preview['blockers'] as $blocker) {
            $this->error('  ✗ '.$blocker['message']);
        }

        foreach ($preview['warnings'] as $warning) {
            $this->warn('  ! '.$warning['key'].' — '.$warning['message']);
        }

        foreach ($preview['changes'] as $change) {
            $symbol = match ($change['verdict']) {
                'create' => '+',
                'update' => '~',
                'unchanged' => '=',
                'conflict' => '✗',
                default => '·',
            };

            $line = '  '.$symbol.' '.str_pad($change['type'], 5).' '.$change['name'];
            if ($change['verdict'] === 'update' && $change['old_content'] !== null) {
                $line .= "\n      قديم: ".$change['old_content'];
                $line .= "\n      جديد: ".$change['content'];
            } elseif ($change['verdict'] === 'conflict') {
                $line .= ' — '.($change['reason'] ?? 'تعارض');
            } else {
                $line .= ' → '.$change['content'];
            }

            if (($change['origin'] ?? '') === 'generated') {
                $line .= ' [مُولَّد]';
            }

            $this->line($line);
        }

        foreach ($preview['extras'] as $extra) {
            $this->warn('  ? '.$extra['type'].' '.$extra['name'].' → '.$extra['content'].' — '.$extra['reason']);
        }

        $this->line('  '.$preview['message']);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function renderResults(array $result): void
    {
        foreach ($result['results'] ?? [] as $record) {
            $record['ok']
                ? $this->line('  ✓ '.$record['type'].' '.$record['name'])
                : $this->error('  ✗ '.$record['type'].' '.$record['name'].' — '.($record['message'] ?? 'فشل'));
        }

        ($result['ok'] ?? false)
            ? $this->info('  '.$result['message'])
            : $this->error('  '.$result['message']);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    protected function summarise(array $result, string $outcome, bool $ok): array
    {
        return [
            'ok' => $ok,
            'outcome' => $outcome,
            'domain' => $result['domain'] ?? null,
            'zone' => $result['zone']['name'] ?? null,
            'counts' => $result['counts'] ?? [],
            'blockers' => array_column($result['blockers'] ?? [], 'key'),
            'warnings' => array_column($result['warnings'] ?? [], 'key'),
            'created_count' => $result['created_count'] ?? 0,
            'updated_count' => $result['updated_count'] ?? 0,
            'failed_count' => $result['failed_count'] ?? 0,
            'message' => $result['message'] ?? null,
        ];
    }
}
