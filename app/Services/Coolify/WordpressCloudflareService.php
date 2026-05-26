<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use App\Services\CloudflareApiService;
use App\Services\CoolifyApiService;

class WordpressCloudflareService
{
    public function __construct(
        protected CloudflareApiService $cloudflare,
        protected CoolifyApiService $coolify,
        protected CoolifySettingsService $settings
    ) {}

    /**
     * @param  array<string, mixed>  $service
     * @return array{ok: bool, message?: string, metadata?: array<string, mixed>}
     */
    public function applyForSite(
        CoolifyWordpressSite $site,
        array $service,
        ?string $preset = null,
        ?callable $log = null
    ): array {
        if (! $this->isEnabledForSite($site)) {
            return ['ok' => true, 'message' => 'Cloudflare معطّل لهذا الموقع'];
        }

        if (! $this->settings->getWordpressCloudflareEnabled()) {
            return ['ok' => true, 'message' => 'Cloudflare معطّل في الإعدادات'];
        }

        if (! $this->cloudflare->isConfigured()) {
            return ['ok' => false, 'message' => 'Cloudflare API غير مضبوط'];
        }

        $zoneId = $this->resolveZoneId();
        if ($zoneId === '') {
            return ['ok' => false, 'message' => 'لم يُحدَّد Zone ID لـ Cloudflare'];
        }

        $preset = $preset ?: $this->settings->getWordpressSecurityPreset();
        $baseDomain = $this->settings->getWordpressBaseDomain();
        $recordName = $site->slug;
        $fqdn = $recordName.'.'.$baseDomain;

        $origin = $this->coolify->extractCoolifyOriginHostname($service);
        if ($origin === null || $origin === '') {
            $ssh = $this->coolify->resolveServerSshHost((string) $site->server_uuid);
            if (($ssh['success'] ?? false) && filter_var($ssh['host'], FILTER_VALIDATE_IP)) {
                $origin = $ssh['host'];
            }
        }

        if ($origin === null || $origin === '') {
            return ['ok' => false, 'message' => 'تعذّر تحديد وجهة DNS (CNAME أو IP السيرفر)'];
        }

        $proxied = $this->settings->getWordpressCloudflareProxied();
        $isIp = filter_var($origin, FILTER_VALIDATE_IP) !== false;

        $this->log($log, 'cloudflare_dns', 'إعداد DNS لـ '.$fqdn.' → '.$origin);

        $existing = $this->cloudflare->findDnsRecordByName(
            $zoneId,
            $recordName,
            $isIp ? 'A' : 'CNAME'
        );
        if ($existing === null) {
            $existing = $this->cloudflare->findDnsRecordByName($zoneId, $fqdn, $isIp ? 'A' : 'CNAME');
        }

        $recordPayload = [
            'type' => $isIp ? 'A' : 'CNAME',
            'name' => $recordName,
            'content' => $origin,
            'proxied' => $proxied,
            'ttl' => 1,
        ];

        if ($existing !== null) {
            $recordId = (string) ($existing['id'] ?? '');
            $response = $this->cloudflare->updateDnsRecord($zoneId, $recordId, $recordPayload);
        } else {
            $response = $this->cloudflare->createDnsRecord($zoneId, $recordPayload);
        }

        if (! ($response['success'] ?? false)) {
            return [
                'ok' => false,
                'message' => $response['message'] ?? 'فشل إنشاء/تحديث سجل DNS على Cloudflare',
            ];
        }

        $recordData = $response['data']['result'] ?? $response['data'] ?? [];
        $recordId = is_array($recordData) ? (string) ($recordData['id'] ?? $existing['id'] ?? '') : '';

        $this->log($log, 'cloudflare_ssl', 'ضبط SSL: '.$this->settings->getWordpressCloudflareSslMode());
        $sslResponse = $this->cloudflare->updateZoneSetting(
            $zoneId,
            'ssl',
            $this->settings->getWordpressCloudflareSslMode()
        );
        if (! ($sslResponse['success'] ?? false)) {
            $this->log($log, 'cloudflare_ssl', 'تحذير SSL: '.($sslResponse['message'] ?? 'فشل'));
        }

        $this->applyPreset($zoneId, $preset, $log);

        $metadata = [
            'cloudflare' => [
                'zone_id' => $zoneId,
                'dns_record_id' => $recordId,
                'record_name' => $recordName,
                'fqdn' => $fqdn,
                'proxied' => $proxied,
                'origin' => $origin,
                'preset' => $preset,
                'ssl_mode' => $this->settings->getWordpressCloudflareSslMode(),
            ],
        ];

        $mergedMeta = array_merge($site->metadata ?? [], $metadata);

        $site->update([
            'metadata' => $mergedMeta,
        ]);
        $site->refresh();

        $fbWarning = null;
        if ($this->settings->getWordpressFilebrowserEnabled()) {
            $withFb = array_merge($mergedMeta, ['filebrowser_enabled' => true]);
            $site->update(['metadata' => $withFb]);
            $site->refresh();
            $wrongHint = $this->detectWrongFilebrowserDnsHint($site, $zoneId);
            $fbWarning = $this->applyFilebrowserDns($site, $proxied, $zoneId, $log);
            if ($wrongHint !== null) {
                $fbWarning = trim(($fbWarning ?? '').' '.$wrongHint);
            }
            $mergedMeta = $site->fresh()->metadata ?? [];
            if ($fbWarning !== null && $fbWarning !== '') {
                $mergedMeta['filebrowser_dns_warning'] = $fbWarning;
            } else {
                unset($mergedMeta['filebrowser_dns_warning']);
            }
            $site->update(['metadata' => $mergedMeta]);
        }

        $this->log($log, 'cloudflare_done', 'اكتمل ربط Cloudflare ('.$preset.')');

        return [
            'ok' => true,
            'metadata' => $metadata['cloudflare'],
        ];
    }

    /**
     * @param  callable(string, string): void|null  $log
     */
    protected function applyFilebrowserDns(
        CoolifyWordpressSite $site,
        bool $proxied,
        string $zoneId,
        ?callable $log = null
    ): ?string {
        if (! $this->settings->getWordpressFilebrowserEnabled()) {
            return null;
        }

        $meta = $site->metadata ?? [];
        if (! ($meta['filebrowser_enabled'] ?? false)) {
            return null;
        }

        $recordName = $this->settings->buildWordpressFilebrowserDnsName($site->slug);
        $baseDomain = $this->settings->getWordpressBaseDomain();
        $fqdn = $recordName.'.'.$baseDomain;
        $target = $this->resolveFilebrowserDnsTarget($site, $proxied);

        $this->log(
            $log,
            'cloudflare_filebrowser_dns',
            'إعداد DNS لـ FileBrowser: '.$fqdn.' → '.$target['type'].' '.$target['content']
        );

        $response = $this->upsertFilebrowserDnsRecord(
            $zoneId,
            $recordName,
            $fqdn,
            $target['type'],
            $target['content'],
            $proxied
        );

        if (! ($response['success'] ?? false)) {
            return $response['message'] ?? 'فشل إنشاء/تحديث سجل DNS لـ FileBrowser';
        }

        $recordData = $response['data']['result'] ?? $response['data'] ?? [];
        $recordId = is_array($recordData) ? (string) ($recordData['id'] ?? '') : '';

        $site->refresh();
        $site->update([
            'metadata' => array_merge($site->metadata ?? [], [
                'cloudflare_filebrowser' => [
                    'zone_id' => $zoneId,
                    'dns_record_id' => $recordId,
                    'record_name' => $recordName,
                    'fqdn' => $fqdn,
                    'proxied' => $proxied,
                    'record_type' => $target['type'],
                    'origin' => $target['content'],
                ],
            ]),
        ]);

        return null;
    }

    /**
     * مع Cloudflare Proxy: files.{slug} → CNAME → {slug}.{domain} (وليس A إلى IP ولا CNAME إلى نفسه).
     *
     * @return array{type: string, content: string}
     */
    protected function resolveFilebrowserDnsTarget(CoolifyWordpressSite $site, bool $proxied): array
    {
        $baseDomain = strtolower(rtrim($this->settings->getWordpressBaseDomain(), '.'));
        $mainFqdn = strtolower($site->slug.'.'.$baseDomain);

        if ($proxied) {
            return [
                'type' => 'CNAME',
                'content' => $mainFqdn,
            ];
        }

        $mainCf = ($site->metadata ?? [])['cloudflare'] ?? [];
        if (isset($mainCf['origin']) && filter_var($mainCf['origin'], FILTER_VALIDATE_IP)) {
            return [
                'type' => 'A',
                'content' => (string) $mainCf['origin'],
            ];
        }

        return [
            'type' => 'CNAME',
            'content' => $mainFqdn,
        ];
    }

    /**
     * @return array{success: bool, message?: string, data?: mixed}
     */
    protected function upsertFilebrowserDnsRecord(
        string $zoneId,
        string $recordName,
        string $fqdn,
        string $type,
        string $content,
        bool $proxied
    ): array {
        $type = strtoupper($type);
        $content = strtolower(rtrim($content, '.'));
        $fbFqdn = strtolower(rtrim($fqdn, '.'));

        if ($content === $fbFqdn) {
            return [
                'success' => false,
                'message' => 'CNAME content cannot reference itself',
            ];
        }

        $existing = $this->cloudflare->findDnsRecordByName($zoneId, $recordName);
        if ($existing === null) {
            $existing = $this->cloudflare->findDnsRecordByName($zoneId, $fqdn);
        }

        $payload = [
            'type' => $type,
            'name' => $recordName,
            'content' => $content,
            'proxied' => $proxied,
            'ttl' => 1,
        ];

        if ($existing !== null) {
            $existingType = strtoupper((string) ($existing['type'] ?? ''));
            $recordId = (string) ($existing['id'] ?? '');

            if ($existingType !== $type) {
                $delete = $this->cloudflare->deleteDnsRecord($zoneId, $recordId);
                if (! ($delete['success'] ?? false)) {
                    return $delete;
                }

                return $this->cloudflare->createDnsRecord($zoneId, $payload);
            }

            return $this->cloudflare->updateDnsRecord($zoneId, $recordId, $payload);
        }

        return $this->cloudflare->createDnsRecord($zoneId, $payload);
    }

    /**
     * يقرأ سجل DNS الموجود على Cloudflare ويحدّث metadata دون إنشاء سجل جديد.
     *
     * @return array{ok: bool, message?: string, metadata?: array<string, mixed>}
     */
    public function syncFromExistingDns(CoolifyWordpressSite $site): array
    {
        if (! $this->isEnabledForSite($site)) {
            return ['ok' => false, 'message' => 'Cloudflare معطّل لهذا الموقع'];
        }

        if (! $this->settings->getWordpressCloudflareEnabled()) {
            return ['ok' => false, 'message' => 'Cloudflare معطّل في إعدادات Coolify'];
        }

        if (! $this->cloudflare->isConfigured()) {
            return ['ok' => false, 'message' => 'Cloudflare API غير مضبوط — احفظ التوكن من إعدادات Cloudflare'];
        }

        $baseDomain = strtolower(rtrim($this->settings->getWordpressBaseDomain(), '.'));
        if ($baseDomain === '') {
            return ['ok' => false, 'message' => 'لم يُحدَّد النطاق الأساسي في إعدادات WordPress'];
        }

        $zoneId = $this->resolveZoneId();
        if ($zoneId === '') {
            return ['ok' => false, 'message' => 'لم يُحدَّد Zone ID — اختر المنطقة في إعدادات Coolify أو تأكد من صلاحيات التوكن'];
        }

        $record = $this->findExistingDnsRecord($zoneId, $site->slug, $baseDomain);
        if ($record === null) {
            $fqdn = $site->slug.'.'.$baseDomain;

            return [
                'ok' => false,
                'message' => 'لم يُعثر على سجل DNS (A أو CNAME) لـ '.$fqdn.' في Cloudflare',
            ];
        }

        $this->settings->persistWordpressCloudflareZoneIdIfEmpty($zoneId);

        $recordFqdn = strtolower(rtrim((string) ($record['name'] ?? ''), '.'));
        if ($recordFqdn !== '' && ! str_contains($recordFqdn, '.')) {
            $recordFqdn = $recordFqdn.'.'.$baseDomain;
        }

        $sslMode = $this->settings->getWordpressCloudflareSslMode();
        $sslResponse = $this->cloudflare->getZoneSsl($zoneId);
        if ($sslResponse['success'] ?? false) {
            $sslValue = $sslResponse['data']['result']['value'] ?? null;
            if (is_string($sslValue) && $sslValue !== '') {
                $sslMode = $sslValue;
            }
        }

        $preset = (string) ($site->metadata['security_preset'] ?? $this->settings->getWordpressSecurityPreset());

        $cloudflareMeta = [
            'zone_id' => $zoneId,
            'dns_record_id' => (string) ($record['id'] ?? ''),
            'record_name' => $site->slug,
            'fqdn' => $recordFqdn !== '' ? $recordFqdn : ($site->slug.'.'.$baseDomain),
            'proxied' => (bool) ($record['proxied'] ?? false),
            'origin' => (string) ($record['content'] ?? ''),
            'record_type' => strtoupper((string) ($record['type'] ?? '')),
            'preset' => $preset,
            'ssl_mode' => $sslMode,
            'synced_at' => now()->toIso8601String(),
            'sync_source' => 'dns_lookup',
        ];

        $merged = array_merge($site->metadata ?? [], [
            'cloudflare' => $cloudflareMeta,
        ]);
        unset($merged['domain_warning']);

        $site->update(['metadata' => $merged]);
        $site->refresh();

        return $this->finalizeSyncWithFilebrowserDns($site, $zoneId, $cloudflareMeta);
    }

    /**
     * مزامنة سجل WordPress من Cloudflare ثم إنشاء/تصحيح سجل FileBrowser (files.{slug}).
     *
     * @param  array<string, mixed>  $mainCloudflareMeta
     * @return array{ok: bool, message?: string, metadata?: array<string, mixed>, main_fqdn?: string, filebrowser_fqdn?: string, filebrowser_warning?: string|null}
     */
    protected function finalizeSyncWithFilebrowserDns(
        CoolifyWordpressSite $site,
        string $zoneId,
        array $mainCloudflareMeta
    ): array {
        $mainFqdn = (string) ($mainCloudflareMeta['fqdn'] ?? '');

        if (! $this->settings->getWordpressFilebrowserEnabled()) {
            return [
                'ok' => true,
                'metadata' => $site->metadata ?? [],
                'main_fqdn' => $mainFqdn,
            ];
        }

        $merged = $site->metadata ?? [];
        $merged['filebrowser_enabled'] = true;
        $site->update(['metadata' => $merged]);
        $site->refresh();

        $proxied = (bool) ($mainCloudflareMeta['proxied'] ?? $this->settings->getWordpressCloudflareProxied());
        $wrongRecordHint = $this->detectWrongFilebrowserDnsHint($site, $zoneId);
        $fbWarning = $this->applyFilebrowserDns($site, $proxied, $zoneId, null);

        $site->refresh();
        $merged = $site->metadata ?? [];
        if ($fbWarning === null) {
            unset($merged['filebrowser_dns_warning']);
        } else {
            $merged['filebrowser_dns_warning'] = $fbWarning;
        }
        if ($wrongRecordHint !== null) {
            $merged['filebrowser_dns_warning'] = trim(
                ($merged['filebrowser_dns_warning'] ?? '').' '.$wrongRecordHint
            );
        }
        $site->update(['metadata' => $merged]);

        $fbMeta = ($site->fresh()->metadata ?? [])['cloudflare_filebrowser'] ?? [];
        $fbFqdn = (string) ($fbMeta['fqdn'] ?? $this->settings->buildWordpressFilebrowserPublicUrl($site->slug));
        $fbFqdn = preg_replace('#^https?://#', '', $fbFqdn);

        $finalWarning = $merged['filebrowser_dns_warning'] ?? null;

        return [
            'ok' => $fbWarning === null && $wrongRecordHint === null,
            'message' => $finalWarning,
            'metadata' => $site->fresh()->metadata ?? [],
            'main_fqdn' => $mainFqdn,
            'filebrowser_fqdn' => $fbFqdn,
            'filebrowser_warning' => $finalWarning,
        ];
    }

    /**
     * يحذر إن وُجد سجل قديم خاطئ مثل files.domain بدل files.{slug}.domain.
     */
    protected function detectWrongFilebrowserDnsHint(CoolifyWordpressSite $site, string $zoneId): ?string
    {
        $baseDomain = strtolower(rtrim($this->settings->getWordpressBaseDomain(), '.'));
        $expectedRecord = $this->settings->buildWordpressFilebrowserDnsName($site->slug);
        $expectedFqdn = strtolower($expectedRecord.'.'.$baseDomain);
        $prefix = strtolower($this->settings->getWordpressFilebrowserSubdomainPrefix());

        $nestedName = $prefix.'.'.$site->slug;
        $flatName = $site->slug.'-'.$prefix;
        $wrongNames = array_unique(array_filter([
            $prefix,
            $prefix.'.'.$baseDomain,
            $nestedName,
            $flatName,
        ]));

        foreach ($wrongNames as $name) {
            if (strtolower($name) === strtolower($expectedRecord)) {
                continue;
            }
            $record = $this->cloudflare->findDnsRecordByName($zoneId, $name);
            if ($record === null) {
                continue;
            }
            $foundFqdn = strtolower(rtrim((string) ($record['name'] ?? ''), '.'));
            if ($foundFqdn === $expectedFqdn) {
                continue;
            }

            return 'يوجد سجل DNS قديم خاطئ («'.$foundFqdn.'») — احذفه من Cloudflare واترك «'.$expectedFqdn.'» فقط (CNAME → '.$site->slug.'.'.$baseDomain.').';
        }

        return null;
    }

    /**
     * تطبيق/تصحيح DNS للموقع وFileBrowser معاً (مثلاً بعد تغيير الإعدادات).
     *
     * @param  array<string, mixed>  $service
     * @return array{ok: bool, message?: string, main_fqdn?: string, filebrowser_fqdn?: string}
     */
    public function applyAllDnsForSite(CoolifyWordpressSite $site, array $service, ?callable $log = null): array
    {
        $main = $this->applyForSite($site, $service, null, $log);
        if (! ($main['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => $main['message'] ?? 'فشل إعداد DNS للموقع',
            ];
        }

        $site->refresh();
        $fbFqdn = (string) (($site->metadata['cloudflare_filebrowser']['fqdn'] ?? '')
            ?: $this->settings->buildWordpressFilebrowserPublicUrl($site->slug));
        $fbFqdn = preg_replace('#^https?://#', '', $fbFqdn);
        $warning = $site->metadata['filebrowser_dns_warning'] ?? null;

        return [
            'ok' => $warning === null,
            'message' => $warning,
            'main_fqdn' => (string) (($main['metadata']['fqdn'] ?? '') ?: $this->settings->buildWordpressPublicUrl($site->slug)),
            'filebrowser_fqdn' => $fbFqdn,
        ];
    }

    public function isEnabledForSite(CoolifyWordpressSite $site): bool
    {
        $meta = $site->metadata ?? [];
        if (array_key_exists('cloudflare_enabled', $meta)) {
            return filter_var($meta['cloudflare_enabled'], FILTER_VALIDATE_BOOLEAN);
        }

        return $this->settings->getWordpressCloudflareEnabled();
    }

    protected function resolveZoneId(): string
    {
        $zoneId = $this->settings->getWordpressCloudflareZoneId();
        if ($zoneId !== '') {
            return $zoneId;
        }

        $base = $this->settings->getWordpressBaseDomain();

        return $this->cloudflare->resolveZoneIdForDomain($base) ?? '';
    }

    /**
     * @param  callable|null  $log  function(string $step, string $message): void
     */
    protected function applyPreset(string $zoneId, string $preset, ?callable $log): void
    {
        $this->log($log, 'cloudflare_preset', 'تطبيق قالب: '.$preset);

        $this->cloudflare->updateZoneSetting($zoneId, 'always_use_https', 'on');

        if ($preset === 'performance') {
            $this->cloudflare->updateZoneSetting($zoneId, 'browser_cache_ttl', 14400);
            $this->cloudflare->updateZoneSetting($zoneId, 'minify', [
                'css' => 'on',
                'html' => 'off',
                'js' => 'on',
            ]);
        }

        if ($preset === 'strict') {
            $this->cloudflare->updateZoneSetting($zoneId, 'security_level', 'high');
            $this->cloudflare->updateZoneSetting($zoneId, 'browser_check', 'on');
        }
    }

    protected function log(?callable $log, string $step, string $message): void
    {
        if ($log !== null) {
            $log($step, $message);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findExistingDnsRecord(string $zoneId, string $slug, string $baseDomain): ?array
    {
        $slug = strtolower($slug);
        $fqdn = $slug.'.'.$baseDomain;

        foreach (['A', 'CNAME'] as $type) {
            foreach ([$fqdn, $slug] as $name) {
                $record = $this->cloudflare->findDnsRecordByName($zoneId, $name, $type);
                if ($record !== null) {
                    return $record;
                }
            }
        }

        foreach ([$fqdn, $slug] as $name) {
            $record = $this->cloudflare->findDnsRecordByName($zoneId, $name, null);
            if ($record !== null) {
                return $record;
            }
        }

        return null;
    }
}
