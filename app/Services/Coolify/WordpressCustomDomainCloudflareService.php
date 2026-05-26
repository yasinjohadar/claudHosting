<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use App\Support\WordpressDomainHelper;
use App\Services\CloudflareApiService;
use App\Services\CoolifyApiService;

class WordpressCustomDomainCloudflareService
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

        if (! $this->cloudflare->isConfigured()) {
            return $this->applyManualProvisioning($site, $service, 'Cloudflare API غير مضبوط — اتبع تعليمات DNS اليدوية.');
        }

        $apex = (string) ($site->custom_domain_apex ?? '');
        $primary = (string) ($site->primary_hostname ?? '');

        if ($apex === '' || $primary === '') {
            return ['ok' => false, 'message' => 'الدومين المستقل غير مكتمل في بيانات الموقع'];
        }

        $zoneId = $this->resolveZoneIdForApex($apex);
        if ($zoneId === '') {
            return $this->applyManualProvisioning(
                $site,
                $service,
                'لم تُعثر على منطقة Cloudflare للدومين «'.$apex.'» — أضف المنطقة أو اضبط DNS يدوياً.'
            );
        }

        $preset = $preset ?: $this->settings->getWordpressSecurityPreset();
        $dns = WordpressDomainHelper::dnsRecordForPrimaryHostname($primary, $apex);

        $origin = $this->resolveOrigin($site, $service);
        if ($origin === '') {
            return ['ok' => false, 'message' => 'تعذّر تحديد وجهة DNS (CNAME أو IP السيرفر)'];
        }

        $proxied = $this->settings->getWordpressCloudflareProxied();
        $isIp = filter_var($origin, FILTER_VALIDATE_IP) !== false;

        $this->log($log, 'cloudflare_dns', 'إعداد DNS لـ '.$dns['fqdn'].' → '.$origin);

        $existing = $this->cloudflare->findDnsRecordByName(
            $zoneId,
            $dns['record_name'],
            $isIp ? 'A' : 'CNAME'
        );
        if ($existing === null) {
            $existing = $this->cloudflare->findDnsRecordByName($zoneId, $dns['fqdn'], $isIp ? 'A' : 'CNAME');
        }

        $recordPayload = [
            'type' => $isIp ? 'A' : 'CNAME',
            'name' => $dns['record_name'],
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

        $sslResponse = $this->cloudflare->updateZoneSetting(
            $zoneId,
            'ssl',
            $this->settings->getWordpressCloudflareSslMode()
        );
        if (! ($sslResponse['success'] ?? false)) {
            $this->log($log, 'cloudflare_ssl', 'تحذير SSL: '.($sslResponse['message'] ?? 'فشل'));
        }

        $this->applyPreset($zoneId, $preset, $log);

        $metadata = array_merge($site->metadata ?? [], [
            'dns_provisioning' => 'cloudflare',
            'cloudflare_zone_id' => $zoneId,
            'cloudflare' => [
                'zone_id' => $zoneId,
                'dns_record_id' => $recordId,
                'record_name' => $dns['record_name'],
                'fqdn' => $dns['fqdn'],
                'proxied' => $proxied,
                'origin' => $origin,
                'preset' => $preset,
                'ssl_mode' => $this->settings->getWordpressCloudflareSslMode(),
                'domain_type' => 'custom',
            ],
        ]);

        unset($metadata['dns_manual_instructions']);

        $site->update(['metadata' => $metadata]);
        $site->refresh();

        $fbWarning = null;
        if ($this->settings->getWordpressFilebrowserEnabled()) {
            $fbWarning = $this->applyFilebrowserDns($site, $zoneId, $apex, $dns['fqdn'], $proxied, $log);
        }

        $this->log($log, 'cloudflare_done', 'اكتمل ربط Cloudflare للدومين المستقل');

        if ($fbWarning !== null && $fbWarning !== '') {
            $merged = $site->fresh()->metadata ?? [];
            $merged['filebrowser_dns_warning'] = $fbWarning;
            $site->update(['metadata' => $merged]);
        }

        return ['ok' => true];
    }

    public function isEnabledForSite(CoolifyWordpressSite $site): bool
    {
        $meta = $site->metadata ?? [];
        if (array_key_exists('cloudflare_enabled', $meta)) {
            return filter_var($meta['cloudflare_enabled'], FILTER_VALIDATE_BOOLEAN);
        }

        return $this->settings->getWordpressCloudflareEnabled();
    }

    protected function resolveZoneIdForApex(string $apex): string
    {
        return $this->cloudflare->resolveZoneIdForDomain($apex) ?? '';
    }

    /**
     * @param  array<string, mixed>  $service
     * @return array{ok: bool, message?: string}
     */
    protected function applyManualProvisioning(
        CoolifyWordpressSite $site,
        array $service,
        string $reason
    ): array {
        $origin = $this->resolveOrigin($site, $service);
        if ($origin === '') {
            $origin = 'عنوان السيرفر أو CNAME من Coolify';
        }

        $apex = (string) $site->custom_domain_apex;
        $primary = (string) $site->primary_hostname;
        $includeFb = $this->settings->getWordpressFilebrowserEnabled();

        $instructions = WordpressDomainHelper::manualDnsInstructions(
            $primary,
            $apex,
            $origin,
            $includeFb
        );

        $site->update([
            'metadata' => array_merge($site->metadata ?? [], [
                'dns_provisioning' => 'manual',
                'dns_manual_instructions' => $instructions,
                'dns_manual_note' => $reason,
                'filebrowser_hostname' => $includeFb
                    ? WordpressDomainHelper::filebrowserHostname($apex)
                    : null,
            ]),
        ]);

        return [
            'ok' => true,
            'message' => $reason,
        ];
    }

    /**
     * @param  array<string, mixed>  $service
     */
    protected function resolveOrigin(CoolifyWordpressSite $site, array $service): string
    {
        $origin = $this->coolify->extractCoolifyOriginHostname($service);
        if ($origin !== null && $origin !== '') {
            return $origin;
        }

        $ssh = $this->coolify->resolveServerSshHost((string) $site->server_uuid);
        if (($ssh['success'] ?? false) && filter_var($ssh['host'], FILTER_VALIDATE_IP)) {
            return (string) $ssh['host'];
        }

        return '';
    }

    protected function applyFilebrowserDns(
        CoolifyWordpressSite $site,
        string $zoneId,
        string $apex,
        string $mainFqdn,
        bool $proxied,
        ?callable $log
    ): ?string {
        $recordName = 'files';
        $fqdn = WordpressDomainHelper::filebrowserHostname($apex);
        $target = $proxied
            ? ['type' => 'CNAME', 'content' => strtolower($mainFqdn)]
            : ['type' => 'CNAME', 'content' => strtolower($mainFqdn)];

        $this->log($log, 'cloudflare_filebrowser_dns', 'إعداد DNS لـ FileBrowser: '.$fqdn);

        $existing = $this->cloudflare->findDnsRecordByName($zoneId, $recordName);
        if ($existing === null) {
            $existing = $this->cloudflare->findDnsRecordByName($zoneId, $fqdn);
        }

        $payload = [
            'type' => $target['type'],
            'name' => $recordName,
            'content' => $target['content'],
            'proxied' => $proxied,
            'ttl' => 1,
        ];

        if ($existing !== null) {
            $response = $this->cloudflare->updateDnsRecord($zoneId, (string) $existing['id'], $payload);
        } else {
            $response = $this->cloudflare->createDnsRecord($zoneId, $payload);
        }

        if (! ($response['success'] ?? false)) {
            return $response['message'] ?? 'فشل إنشاء/تحديث سجل DNS لـ FileBrowser';
        }

        $recordData = $response['data']['result'] ?? $response['data'] ?? [];
        $recordId = is_array($recordData) ? (string) ($recordData['id'] ?? '') : '';

        $site->update([
            'metadata' => array_merge($site->metadata ?? [], [
                'filebrowser_hostname' => $fqdn,
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
}
