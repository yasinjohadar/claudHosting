<?php

namespace App\Services\Whm\MailDns;

use App\Models\WhmAccount;
use App\Services\CloudflareApiService;
use App\Services\Whm\WhmAccountService;
use App\Services\Whm\WhmEmailDeliverabilityService;
use App\Support\Dns\DnsValue;
use Illuminate\Support\Facades\Log;

/**
 * Mirrors a cPanel account's mail DNS into its Cloudflare zone.
 *
 * This writes to live DNS for a domain that may be carrying real mail, so the design is
 * deliberately conservative:
 *  - the desired values come from cPanel's own zone, never from a locally-derived guess;
 *  - nothing is ever deleted, only created or updated;
 *  - every payload name must sit inside the account's domain (allow-list, not a check);
 *  - preview and apply share one code path, so what you saw is what runs;
 *  - apply is idempotent, which is what makes a partial failure safe to simply re-run.
 *
 * Returns array{ok: bool, ...} — `ok` not `success`, matching the orchestration
 * convention in WordpressCloudflareService (the raw API envelope uses `success`).
 */
class WhmMailDnsSyncService
{
    /** Hard refusals: the operator cannot acknowledge their way past these. */
    public const BLOCKER_TERMINATED = 'account_terminated';

    public const BLOCKER_WHM_ZONE_UNREADABLE = 'whm_zone_unreadable';

    public const BLOCKER_THIRD_PARTY_MX = 'third_party_mx';

    public const BLOCKER_NOTHING_TO_DO = 'nothing_to_plan';

    /** Warnings that must be acknowledged before applying. */
    public const ACK_DMARC_GENERATED = 'dmarc_generated';

    public const ACK_ACCOUNT_SUSPENDED = 'account_suspended';

    public const ACK_MAIL_PROXIED = 'mail_a_proxied';

    public const ACK_EXTRA_RECORDS = 'extra_records';

    public function __construct(
        protected WhmAccountService $accounts,
        protected WhmEmailDeliverabilityService $deliverability,
        protected CloudflareApiService $cloudflare,
        protected WhmMailDnsPlanBuilder $planner,
        protected WhmMailDnsDiffer $differ,
        protected MailDnsSyncLogger $logger
    ) {}

    /**
     * Build the plan and diff it against Cloudflare. Never writes anything.
     *
     * @return array<string, mixed>
     */
    public function preview(WhmAccount $account, ?string $domain = null, bool $fresh = false): array
    {
        $domain = DnsValue::host($domain ?: $account->domain);

        if ($account->status === 'terminated') {
            return $this->blocked($account, $domain, self::BLOCKER_TERMINATED, 'الحساب محذوف — لا منطقة DNS لنسخها');
        }

        if ($domain === '') {
            return $this->blocked($account, $domain, 'invalid_domain', 'نطاق غير صالح');
        }

        $zoneResult = $this->accounts->dnsZoneForDomain($domain);
        if (! ($zoneResult['success'] ?? false)) {
            return $this->blocked(
                $account,
                $domain,
                self::BLOCKER_WHM_ZONE_UNREADABLE,
                $zoneResult['message'] ?? 'تعذّر قراءة منطقة DNS من WHM — لا مصدر حقيقة للسجلات'
            );
        }

        $server = $this->deliverability->serverContextForAccount($account);
        $plan = $this->planner->build($domain, $zoneResult['records'] ?? []);

        if ($plan['items'] === []) {
            return $this->blocked(
                $account,
                $domain,
                self::BLOCKER_NOTHING_TO_DO,
                'لا سجلات بريد قابلة للتركيب — فعّل DKIM و MX في cPanel أولاً',
                ['plan' => $plan, 'server' => $server]
            );
        }

        if ($fresh) {
            $this->cloudflare->forgetZoneListCache();
        }

        $zone = $this->cloudflare->isConfigured()
            ? $this->cloudflare->resolveZoneForHostname($domain)
            : null;

        if ($zone === null) {
            // Still a useful screen: the plan came from WHM alone, so it renders as a
            // manual copy-paste table even with Cloudflare entirely unconfigured.
            return [
                'ok' => true,
                'can_apply' => false,
                'account_id' => $account->id,
                'domain' => $domain,
                'zone' => null,
                'server' => $server,
                'plan' => $plan,
                'changes' => array_map(
                    fn (array $item): array => array_merge($item, [
                        'verdict' => 'manual',
                        'record_id' => null,
                        'old_content' => null,
                        'old_priority' => null,
                        'old_proxied' => null,
                        'reason' => null,
                    ]),
                    $plan['items']
                ),
                'extras' => [],
                'counts' => ['create' => 0, 'update' => 0, 'unchanged' => 0, 'conflict' => 0],
                'blockers' => [[
                    'key' => 'zone_not_found',
                    'message' => $this->cloudflare->isConfigured()
                        ? 'النطاق غير مُدار على Cloudflare — ركّب السجلات يدوياً عند مزوّد DNS'
                        : 'إعدادات Cloudflare غير مكتملة — ركّب السجلات يدوياً أو اضبط التوكن',
                ]],
                'warnings' => [],
                'plan_hash' => null,
                'message' => 'تم بناء الخطة، ولا يمكن تطبيقها تلقائياً',
            ];
        }

        $records = $this->cloudflare->listDnsRecords($zone['id']);
        if (isset($records['_error'])) {
            return $this->blocked(
                $account,
                $domain,
                'cloudflare_unreadable',
                $records['_error'] ?: 'تعذّر قراءة سجلات Cloudflare',
                ['plan' => $plan, 'server' => $server, 'zone' => $zone]
            );
        }

        $diff = $this->differ->diff($plan['items'], $records);

        return [
            'ok' => true,
            'can_apply' => $this->actionable($diff['changes']) !== [],
            'account_id' => $account->id,
            'domain' => $domain,
            'zone' => $zone,
            'server' => $server,
            'plan' => $plan,
            'changes' => $diff['changes'],
            'extras' => $diff['extras'],
            'counts' => $diff['counts'],
            'blockers' => $this->blockers($diff),
            'warnings' => $this->warnings($account, $plan, $diff),
            'plan_hash' => $this->planHash($domain, $zone['id'], $diff['changes']),
            'message' => $this->previewMessage($diff['counts']),
        ];
    }

    /**
     * Re-derive the plan and apply it.
     *
     * The plan is deliberately rebuilt rather than trusted from the request: comparing a
     * hash of the freshly-derived plan against the one the operator saw verifies reality,
     * not just a signature, so a zone that changed (or a DKIM key cPanel rotated) between
     * preview and confirm cannot be applied blind.
     *
     * @param  list<string>  $acknowledged
     * @return array<string, mixed>
     */
    public function apply(
        WhmAccount $account,
        ?string $domain = null,
        ?string $planHash = null,
        array $acknowledged = [],
        bool $dryRun = false,
        string $source = 'web'
    ): array {
        $preview = $this->preview($account, $domain, fresh: true);

        if (! ($preview['ok'] ?? false) || $preview['blockers'] !== []) {
            return array_merge($preview, [
                'ok' => false,
                'outcome' => 'blocked',
                'results' => [],
            ]);
        }

        if ($planHash !== null && ! hash_equals((string) $preview['plan_hash'], $planHash)) {
            return array_merge($preview, [
                'ok' => false,
                'outcome' => 'blocked',
                'results' => [],
                'message' => 'تغيّرت الخطة منذ المعاينة (تعديل في المنطقة أو تدوير مفتاح DKIM من cPanel) — أعد المعاينة ثم التطبيق',
            ]);
        }

        $unacknowledged = array_values(array_diff(
            array_column($preview['warnings'], 'key'),
            $acknowledged
        ));
        if ($unacknowledged !== []) {
            return array_merge($preview, [
                'ok' => false,
                'outcome' => 'blocked',
                'results' => [],
                'unacknowledged' => $unacknowledged,
                'message' => 'يجب الإقرار بالتحذيرات قبل التطبيق: '.implode('، ', $unacknowledged),
            ]);
        }

        $actionable = $this->actionable($preview['changes']);
        if ($actionable === []) {
            return array_merge($preview, [
                'ok' => true,
                'outcome' => 'applied',
                'results' => [],
                'message' => 'كل السجلات مطابقة بالفعل — لا تغييرات',
            ]);
        }

        if ($dryRun) {
            return array_merge($preview, [
                'ok' => true,
                'outcome' => 'dry_run',
                'results' => [],
                'message' => 'معاينة فقط — لم يُكتب شيء ('.count($actionable).' تغيير مُخطَّط)',
            ]);
        }

        return $this->runApply($account, $preview, $actionable, $source);
    }

    /**
     * @param  array<string, mixed>  $preview
     * @param  list<array<string, mixed>>  $actionable
     * @return array<string, mixed>
     */
    protected function runApply(WhmAccount $account, array $preview, array $actionable, string $source): array
    {
        $zoneId = $preview['zone']['id'];
        $domain = $preview['domain'];

        $results = [];
        $created = 0;
        $updated = 0;
        $failed = 0;
        $abortReason = null;

        foreach ($actionable as $change) {
            // Allow-list, not a validation: nothing outside the account's domain is
            // writable, whatever the plan says.
            if (! DnsValue::isWithin($change['name'], $domain)) {
                $results[] = $this->result($change, false, 'اسم السجل خارج نطاق الحساب — رُفض');
                $failed++;

                continue;
            }

            $payload = $this->payloadFor($change);

            $response = $change['verdict'] === 'create'
                ? $this->cloudflare->createDnsRecord($zoneId, $payload)
                : $this->cloudflare->updateDnsRecord($zoneId, (string) $change['record_id'], $payload);

            if ($response['success'] ?? false) {
                $change['verdict'] === 'create' ? $created++ : $updated++;
                $results[] = $this->result($change, true, null);

                continue;
            }

            $failed++;
            $status = (int) ($response['status'] ?? 0);
            $results[] = $this->result($change, false, $response['message'] ?? 'فشل غير معروف', $status);

            // 403 and 429 are global signals, not per-record problems: continuing would
            // just pile up identical failures.
            if (in_array($status, [401, 403], true)) {
                $abortReason = 'token_lacks_dns_edit';
                break;
            }
            if ($status === 429) {
                $abortReason = 'rate_limited';
                break;
            }
        }

        $outcome = $failed === 0 ? 'applied' : (($created + $updated) > 0 ? 'partial' : 'failed');

        $message = $this->applyMessage($outcome, $created, $updated, $failed, count($actionable), $abortReason);

        $logId = $this->logger->record([
            'whm_account_id' => $account->id,
            'domain' => $domain,
            'zone_id' => $zoneId,
            'zone_name' => $preview['zone']['name'] ?? null,
            'source' => $source,
            'outcome' => $outcome,
            'created_count' => $created,
            'updated_count' => $updated,
            'failed_count' => $failed,
            'message' => $message,
            'meta' => ['records' => $results, 'abort_reason' => $abortReason],
        ]);

        if ($outcome !== 'applied') {
            Log::warning('Mail DNS sync did not fully apply', [
                'account' => $account->username,
                'domain' => $domain,
                'outcome' => $outcome,
                'failed' => $failed,
                'abort_reason' => $abortReason,
            ]);
        }

        return array_merge($preview, [
            'ok' => $outcome === 'applied',
            'outcome' => $outcome,
            'results' => $results,
            'created_count' => $created,
            'updated_count' => $updated,
            'failed_count' => $failed,
            'abort_reason' => $abortReason,
            'log_id' => $logId,
            'message' => $message,
        ]);
    }

    /**
     * Cloudflare's update is a PUT (full replace), so the payload must be complete or
     * fields get wiped. `proxied` is omitted entirely for MX/TXT — Cloudflare rejects it
     * there — forced false for the mail host, and carried forward otherwise.
     *
     * @param  array<string, mixed>  $change
     * @return array<string, mixed>
     */
    protected function payloadFor(array $change): array
    {
        $type = strtoupper($change['type']);

        $payload = [
            'type' => $type,
            'name' => $change['name'],
            'content' => $change['content'],
            'ttl' => 1, // 1 = automatic
        ];

        if ($type === 'MX') {
            $payload['priority'] = (int) ($change['priority'] ?? 0);
        }

        if (in_array($type, ['A', 'AAAA', 'CNAME'], true)) {
            $payload['proxied'] = ($change['proxy'] ?? 'inherit') === 'off'
                ? false
                : (bool) ($change['old_proxied'] ?? false);
        }

        if (! empty($change['old_comment'])) {
            $payload['comment'] = $change['old_comment'];
        }

        return $payload;
    }

    /**
     * @param  list<array<string, mixed>>  $changes
     * @return list<array<string, mixed>>
     */
    protected function actionable(array $changes): array
    {
        return array_values(array_filter(
            $changes,
            fn (array $c): bool => in_array($c['verdict'], ['create', 'update'], true)
        ));
    }

    /**
     * @param  array<string, mixed>  $diff
     * @return list<array{key: string, message: string}>
     */
    protected function blockers(array $diff): array
    {
        $blockers = [];

        if ($diff['third_party_mx'] !== []) {
            $providers = implode('، ', array_unique(array_column($diff['third_party_mx'], 'provider')));
            $blockers[] = [
                'key' => self::BLOCKER_THIRD_PARTY_MX,
                'message' => 'سجل MX الحالي يشير إلى '.$providers.' — التطبيق سيوقف بريداً عاملاً. '
                    .'أزِل ربط المزوّد الآخر يدوياً أولاً إن كنت تريد النقل إلى cPanel',
            ];
        }

        return $blockers;
    }

    /**
     * @param  array<string, mixed>  $plan
     * @param  array<string, mixed>  $diff
     * @return list<array{key: string, message: string}>
     */
    protected function warnings(WhmAccount $account, array $plan, array $diff): array
    {
        $warnings = [];

        if ($account->status === 'suspended') {
            $warnings[] = [
                'key' => self::ACK_ACCOUNT_SUSPENDED,
                'message' => 'الحساب موقوف — السجلات ستُركَّب لكن البريد لن يعمل حتى إلغاء الإيقاف',
            ];
        }

        foreach ($plan['items'] as $item) {
            if (($item['origin'] ?? '') === 'generated' && $item['key'] === 'dmarc') {
                $warnings[] = [
                    'key' => self::ACK_DMARC_GENERATED,
                    'message' => 'سجل DMARC مُولَّد محلياً لا منقول من cPanel — راجع القيمة قبل التطبيق',
                ];
                break;
            }
        }

        foreach ($diff['changes'] as $change) {
            if ($change['key'] === 'mail_host' && ($change['old_proxied'] ?? false) === true) {
                $warnings[] = [
                    'key' => self::ACK_MAIL_PROXIED,
                    'message' => 'سجل mail مُفعّل عليه بروكسي Cloudflare — وهذا يمنع SMTP. التطبيق سيُطفئه',
                ];
                break;
            }
        }

        if ($diff['extras'] !== []) {
            $warnings[] = [
                'key' => self::ACK_EXTRA_RECORDS,
                'message' => 'توجد '.count($diff['extras']).' سجلات إضافية لم نخطّط لها ولن تُحذف — راجعها يدوياً',
            ];
        }

        return $warnings;
    }

    /**
     * @param  array<string, mixed>  $change
     * @return array<string, mixed>
     */
    protected function result(array $change, bool $ok, ?string $message, int $status = 0): array
    {
        return [
            'key' => $change['key'],
            'label' => $change['label'],
            'type' => $change['type'],
            'name' => $change['name'],
            'verdict' => $change['verdict'],
            'before' => $change['old_content'],
            'after' => $change['content'],
            'ok' => $ok,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * Stable fingerprint of exactly what would be written.
     *
     * @param  list<array<string, mixed>>  $changes
     */
    protected function planHash(string $domain, string $zoneId, array $changes): string
    {
        $parts = [];

        foreach ($this->actionable($changes) as $change) {
            $parts[] = implode('|', [
                $change['verdict'],
                strtoupper($change['type']),
                DnsValue::host($change['name']),
                DnsValue::content($change['type'], $change['content']),
                (string) ($change['priority'] ?? ''),
                (string) ($change['record_id'] ?? ''),
            ]);
        }

        sort($parts);

        return hash('sha256', $domain.'|'.$zoneId.'|'.implode(';', $parts));
    }

    /**
     * @param  array<string, int>  $counts
     */
    protected function previewMessage(array $counts): string
    {
        $pending = ($counts['create'] ?? 0) + ($counts['update'] ?? 0);

        if ($pending === 0) {
            return 'كل سجلات البريد مطابقة بالفعل — لا تغييرات مطلوبة';
        }

        return 'مطلوب '.$pending.' تغيير: '.($counts['create'] ?? 0).' إنشاء و '.($counts['update'] ?? 0).' تحديث';
    }

    protected function applyMessage(
        string $outcome,
        int $created,
        int $updated,
        int $failed,
        int $total,
        ?string $abortReason
    ): string {
        $done = $created + $updated;

        if ($outcome === 'applied') {
            return 'تم تطبيق '.$done.' تغيير على Cloudflare ('.$created.' إنشاء و '.$updated.' تحديث)';
        }

        $suffix = match ($abortReason) {
            'token_lacks_dns_edit' => ' توكن Cloudflare لا يملك صلاحية تعديل DNS — اضبطه من إعدادات Cloudflare.',
            'rate_limited' => ' تم تجاوز حد طلبات Cloudflare — أعد المحاولة بعد دقيقة.',
            default => '',
        };

        if ($outcome === 'failed') {
            return 'فشل التطبيق — لم يُكتب أي سجل ('.$failed.' فشل).'.$suffix;
        }

        return 'تم تطبيق '.$done.' من '.$total.' تغييرات. المنطقة الآن في حالة مختلطة: '
            .'الناجح مطبَّق والفاشل لم يُطبَّق. أعد المعاينة ثم التطبيق — العملية آمنة للتكرار.'.$suffix;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function blocked(WhmAccount $account, string $domain, string $key, string $message, array $extra = []): array
    {
        return array_merge([
            'ok' => false,
            'can_apply' => false,
            'account_id' => $account->id,
            'domain' => $domain,
            'zone' => null,
            'server' => ['hostname' => null, 'ip' => null, 'ptr' => null, 'ptr_state' => 'unknown'],
            'plan' => ['items' => [], 'skipped' => [], 'notes' => []],
            'changes' => [],
            'extras' => [],
            'counts' => ['create' => 0, 'update' => 0, 'unchanged' => 0, 'conflict' => 0],
            'blockers' => [['key' => $key, 'message' => $message]],
            'warnings' => [],
            'plan_hash' => null,
            'message' => $message,
        ], $extra);
    }
}
