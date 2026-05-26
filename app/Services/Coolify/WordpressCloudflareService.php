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

        $fbWarning = $this->applyFilebrowserDns($site, $origin, $proxied, $isIp, $zoneId, $log);
        if ($fbWarning !== null) {
            $mergedMeta['filebrowser_dns_warning'] = $fbWarning;
        }

        $site->update([
            'metadata' => $mergedMeta,
        ]);

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
        string $origin,
        bool $proxied,
        bool $isIp,
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

        $this->log($log, 'cloudflare_filebrowser_dns', 'إعداد DNS لـ FileBrowser: '.$fqdn.' → '.$origin);

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
            return $response['message'] ?? 'فشل إنشاء/تحديث سجل DNS لـ FileBrowser';
        }

        $recordData = $response['data']['result'] ?? $response['data'] ?? [];
        $recordId = is_array($recordData) ? (string) ($recordData['id'] ?? $existing['id'] ?? '') : '';

        $site->refresh();
        $site->update([
            'metadata' => array_merge($site->metadata ?? [], [
                'cloudflare_filebrowser' => [
                    'zone_id' => $zoneId,
                    'dns_record_id' => $recordId,
                    'record_name' => $recordName,
                    'fqdn' => $fqdn,
                    'proxied' => $proxied,
                    'origin' => $origin,
                ],
            ]),
        ]);

        return null;
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

        return [
            'ok' => true,
            'metadata' => $cloudflareMeta,
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
