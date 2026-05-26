<?php

namespace App\Actions\Coolify;

use App\Models\CoolifyWordpressSite;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Coolify\Wordpress\Domain\WordpressSiteDomainResolver;
use App\Support\WordpressDomainHelper;
use Illuminate\Validation\ValidationException;

class CreateWordpressSiteAction
{
    public function __construct(
        protected CoolifySettingsService $settings,
        protected WordpressSiteDomainResolver $domainResolver
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(array $validated, ?int $createdBy): CoolifyWordpressSite
    {
        $domainType = (string) ($validated['domain_type'] ?? CoolifyWordpressSite::DOMAIN_TYPE_PLATFORM);

        if ($domainType === CoolifyWordpressSite::DOMAIN_TYPE_CUSTOM) {
            if (! $this->settings->getWordpressCustomDomainEnabled()) {
                throw ValidationException::withMessages([
                    'domain_type' => 'خيار الدومين المستقل غير مفعّل حالياً.',
                ]);
            }

            return $this->createCustomDomainSite($validated, $createdBy);
        }

        return $this->createPlatformSubdomainSite($validated, $createdBy);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function createPlatformSubdomainSite(array $validated, ?int $createdBy): CoolifyWordpressSite
    {
        $slug = strtolower(trim((string) $validated['slug']));
        $publicUrl = $this->settings->buildWordpressPublicUrl($slug);

        return CoolifyWordpressSite::create([
            'display_name' => $validated['display_name'],
            'slug' => $slug,
            'domain_type' => CoolifyWordpressSite::DOMAIN_TYPE_PLATFORM,
            'primary_hostname' => null,
            'custom_domain_apex' => null,
            'project_mode' => $validated['project_mode'],
            'project_uuid' => $validated['project_uuid'] ?? null,
            'server_uuid' => $validated['server_uuid'],
            'environment_name' => $validated['environment_name'] ?? $this->settings->getWordpressDefaultEnvironment(),
            'public_url' => $publicUrl,
            'admin_url' => rtrim($publicUrl, '/').'/wp-admin',
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'metadata' => $this->baseMetadata($validated),
            'created_by' => $createdBy,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function createCustomDomainSite(array $validated, ?int $createdBy): CoolifyWordpressSite
    {
        $primaryHost = $this->resolvePrimaryHostname($validated);
        $apex = WordpressDomainHelper::apexFromHostname($primaryHost);
        $slug = strtolower(trim((string) $validated['slug']));
        $publicUrl = WordpressDomainHelper::buildPublicUrl($primaryHost);

        return CoolifyWordpressSite::create([
            'display_name' => $validated['display_name'],
            'slug' => $slug,
            'domain_type' => CoolifyWordpressSite::DOMAIN_TYPE_CUSTOM,
            'primary_hostname' => $primaryHost,
            'custom_domain_apex' => $apex,
            'project_mode' => $validated['project_mode'],
            'project_uuid' => $validated['project_uuid'] ?? null,
            'server_uuid' => $validated['server_uuid'],
            'environment_name' => $validated['environment_name'] ?? $this->settings->getWordpressDefaultEnvironment(),
            'public_url' => $publicUrl,
            'admin_url' => rtrim($publicUrl, '/').'/wp-admin',
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'metadata' => array_merge($this->baseMetadata($validated), [
                'dns_provisioning' => 'pending',
                'filebrowser_hostname' => $this->settings->getWordpressFilebrowserEnabled()
                    ? WordpressDomainHelper::filebrowserHostname($apex)
                    : null,
            ]),
            'created_by' => $createdBy,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function baseMetadata(array $validated): array
    {
        return [
            'cloudflare_enabled' => filter_var(
                $validated['cloudflare_enabled'] ?? $this->settings->getWordpressCloudflareEnabled(),
                FILTER_VALIDATE_BOOLEAN
            ),
            'security_preset' => $validated['security_preset'] ?? $this->settings->getWordpressSecurityPreset(),
            'job_dispatched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function resolvePrimaryHostname(array $validated): string
    {
        $hostChoice = (string) ($validated['custom_host_choice'] ?? 'apex');
        $apexInput = WordpressDomainHelper::normalizeHostname((string) ($validated['custom_domain_apex_input'] ?? ''));

        if ($apexInput === '') {
            throw ValidationException::withMessages([
                'custom_domain_apex_input' => 'أدخل اسم الدومين المستقل.',
            ]);
        }

        if ($hostChoice === 'www') {
            return WordpressDomainHelper::normalizeHostname('www.'.$apexInput);
        }

        return $apexInput;
    }
}
