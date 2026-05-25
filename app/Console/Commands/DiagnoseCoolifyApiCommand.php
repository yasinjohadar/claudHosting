<?php

namespace App\Console\Commands;

use App\Services\CoolifyApiService;
use Illuminate\Console\Command;

class DiagnoseCoolifyApiCommand extends Command
{
    protected $signature = 'coolify:diagnose-api {--clear-cache : مسح كاش إحصائيات اللوحة}';

    protected $description = 'فحص اتصال Coolify API وسبب ظهور الموارد بصفر على اللوحة';

    public function handle(CoolifyApiService $coolify): int
    {
        if ($this->option('clear-cache')) {
            $coolify->clearDashboardCache();
            $this->info('تم مسح كاش coolify_dashboard_stats');
        }

        $config = app(\App\Services\Coolify\CoolifySettingsService::class)->getConnectionConfig();
        $this->info('API URL: '.($config['api_url'] ?: '(فارغ)'));
        $this->line('Token: '.(($config['token_configured'] ?? false) ? 'مُعدّ' : 'غير مُعدّ'));
        $this->line('Base (داخلي): '.$coolify->getBaseUrl());
        $this->newLine();

        if (! $coolify->isConfigured()) {
            $this->error('Coolify غير مضبوط — احفظ URL والتوكن من /admin/coolify/settings');

            return self::FAILURE;
        }

        $this->info('اختبارات أساسية:');
        foreach ([
            'health' => fn () => $coolify->getHealth(),
            'version' => fn () => $coolify->getVersion(),
        ] as $label => $fn) {
            $res = $fn();
            $this->printResult($label, $res);
        }

        $this->newLine();
        $this->info('الفريق المرتبط بالتوكن:');
        $teamRes = $coolify->getCurrentTeam();
        if ($teamRes['success'] ?? false) {
            $team = $teamRes['data'] ?? [];
            if (is_array($team)) {
                $this->line('  الاسم: '.($team['name'] ?? $team['team_name'] ?? '—'));
                $this->line('  UUID: '.($team['uuid'] ?? $team['id'] ?? '—'));
            } else {
                $this->line('  '.json_encode($team, JSON_UNESCAPED_UNICODE));
            }
        } else {
            $this->warn('  تعذر جلب teams/current: '.($teamRes['message'] ?? '—'));
        }

        $teamsRes = $coolify->listTeams();
        if ($teamsRes['success'] ?? false) {
            $teams = $coolify->normalizeList($teamsRes['data'] ?? []);
            $this->line('  عدد الفرق المرئية للتوكن: '.count($teams));
        }

        $this->newLine();
        $this->info('قوائم الموارد (ما تعتمد عليه لوحة Coolify):');

        $endpoints = [
            'servers' => fn () => $coolify->listServers(),
            'projects' => fn () => $coolify->listProjects(),
            'applications' => fn () => $coolify->listApplications(),
            'databases' => fn () => $coolify->listDatabases(),
            'services' => fn () => $coolify->listServices(),
            'deployments' => fn () => $coolify->listDeployments(),
        ];

        $anyListOk = false;
        $totalItems = 0;
        foreach ($endpoints as $label => $fn) {
            $res = $fn();
            $count = ($res['success'] ?? false)
                ? count($coolify->normalizeList($res['data'] ?? []))
                : 0;
            $totalItems += $count;
            if ($res['success'] ?? false) {
                $anyListOk = true;
                if ($count === 0 && $label === 'servers') {
                    $this->line('  <fg=yellow>?</> استجابة servers (مقتطف): '.$this->summarizePayload($res['data'] ?? null));
                }
            }
            $this->printResult($label, $res, $count);
        }

        $this->newLine();
        if (! $anyListOk) {
            $this->warn('الاتصال «متصل» لكن كل القوائم فشلت — الأرقام في اللوحة ستبقى 0.');
            $this->line('الحلول الشائعة على Coolify:');
            $this->line('  1) Keys & Tokens → API Token → Allowed IPs: اتركه فارغاً أو 0.0.0.0 أو أضف IP سيرفر الاستضافة (خروج PHP).');
            $this->line('  2) أنشئ التوكن بصلاحية root/* على نفس الفريق الذي يملك السيرفرات.');
            $this->line('  3) تأكد أن API URL يشير لوحة Coolify (مثل https://coolify.example.com) وليس موقع Laravel فقط.');
            $this->line('  4) راجع storage/logs/laravel.log بعد التحديث — سجلات Coolify API error.');

            return self::FAILURE;
        }

        if ($totalItems === 0) {
            $this->warn('API يعمل (HTTP 200) لكن لا توجد موارد لهذا التوكن/الفريق على coolify.claudsoft.com.');
            $this->line('');
            $this->line('هذا ليس خطأ Laravel — معناه واحد من التالي:');
            $this->line('  • لوحة Coolify على هذا الرابط فارغة (تثبيت جديد بدون سيرفرات/خدمات).');
            $this->line('  • التوكن مربوط بفريق (Team) فارغ — أنشئ التوكن من الفريق الذي فيه السيرفر 194.163.144.165 و site1.');
            $this->line('  • site1 وُجد على Coolify آخر (لوكال أو IP مختلف) — غيّر API URL أو انقل الموارد.');
            $this->line('');
            $this->line('تحقق: افتح https://coolify.claudsoft.com في المتصفح — هل ترى السيرفرات والخدمات هناك؟');
            $this->line('إن كانت موجودة في الواجهة لكن API = 0 → أنشئ API Token جديداً من نفس الفريق النشط في الشريط العلوي للوحة.');

            return self::FAILURE;
        }

        $this->info('✓ القوائم تعمل — '.$totalItems.' مورد إجمالاً. حدّث اللوحة: ?refresh=1');

        return self::SUCCESS;
    }

    protected function summarizePayload(mixed $data): string
    {
        if ($data === null) {
            return '(null)';
        }
        if (is_array($data)) {
            if (array_is_list($data)) {
                return 'مصفوفة ['.count($data).' عنصر]';
            }

            return 'كائن {'.implode(', ', array_slice(array_keys($data), 0, 8)).'}';
        }

        return substr((string) $data, 0, 120);
    }

    /**
     * @param  array{success?: bool, message?: string, status?: int, data?: mixed}  $res
     */
    protected function printResult(string $label, array $res, ?int $count = null): void
    {
        $ok = $res['success'] ?? false;
        $status = $res['status'] ?? '—';
        $suffix = $count !== null ? " → {$count} عنصر" : '';
        if ($ok) {
            $this->line("  <fg=green>✓</> {$label} (HTTP {$status}){$suffix}");
        } else {
            $msg = $res['message'] ?? 'فشل';
            $this->line("  <fg=red>✗</> {$label} (HTTP {$status}): {$msg}");
        }
    }
}
