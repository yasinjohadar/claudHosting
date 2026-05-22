<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use App\Services\CoolifyApiService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class WordpressSiteProvisioningService
{
    protected const POLL_INTERVAL_SECONDS = 15;

    protected const MAX_POLL_ATTEMPTS = 60;

    /** مهلة أولية قبل اعتبار exited فشلاً (سحب الصور + تهيئة MariaDB) */
    protected const DEPLOY_GRACE_SECONDS = 180;

    protected const INITIAL_RESTART_SLEEP_SECONDS = 30;

    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifySettingsService $settings,
        protected WordpressCloudflareService $wordpressCloudflare
    ) {}

    public function provision(CoolifyWordpressSite $site): void
    {
        $metadata = $site->metadata ?? [];
        unset($metadata['last_api']);

        $site->update([
            'status' => 'provisioning',
            'error_message' => null,
            'metadata' => $metadata,
        ]);

        $this->appendProvisionLog($site, 'start', 'بدء إنشاء الموقع على Coolify');

        try {
            $projectUuid = $this->resolveProjectUuid($site);
            $site->update(['project_uuid' => $projectUuid]);
            $this->appendProvisionLog($site, 'create_project', 'المشروع جاهز: '.$projectUuid);

            $publicUrl = $site->public_url ?: $this->settings->buildWordpressPublicUrl($site->slug);

            $serviceUuid = $site->service_uuid;
            if ($serviceUuid === null || $serviceUuid === '') {
                $this->appendProvisionLog($site, 'create_service', 'إنشاء خدمة WordPress...');
                $serviceUuid = $this->createWordpressService($site, $projectUuid, $publicUrl);
                $site->update([
                    'service_uuid' => $serviceUuid,
                    'public_url' => $publicUrl,
                ]);
                $this->appendProvisionLog($site, 'create_service', 'تم إنشاء الخدمة: '.$serviceUuid);
            } else {
                $this->appendProvisionLog($site, 'create_service', 'استخدام خدمة موجودة: '.$serviceUuid);
            }

            $this->appendProvisionLog($site, 'deploy', 'إرسال طلب تشغيل/إعادة تشغيل على Coolify...');
            $this->triggerServiceDeploy($serviceUuid);

            $this->waitUntilRunning($site, $serviceUuid);

            $this->appendProvisionLog($site, 'apply_domain', 'تعيين النطاق: '.$publicUrl);
            $domainWarning = $this->applyUrlsToService($serviceUuid, $publicUrl);

            $service = $this->fetchService($serviceUuid);

            $cloudflareWarning = $this->applyCloudflare($site, $service);
            if ($cloudflareWarning !== null) {
                $domainWarning = $domainWarning
                    ? $domainWarning.' | '.$cloudflareWarning
                    : $cloudflareWarning;
            }
            $envs = $this->coolify->normalizeList($this->coolify->listServiceEnvs($serviceUuid)['data'] ?? []);

            $resolvedPublic = $this->coolify->extractServicePublicUrl($service) ?: $publicUrl;
            $adminUrl = $this->coolify->extractWordpressAdminUrl($service, $envs);
            $dbEnv = $this->coolify->extractDatabaseEnvFromServiceEnvs($envs);

            $metadata = array_merge($site->metadata ?? [], [
                'service' => Arr::only($service, ['uuid', 'name', 'status', 'type', 'fqdn', 'domains']),
                'database_env' => $dbEnv,
                'provisioned_at' => now()->toIso8601String(),
            ]);

            if ($domainWarning !== null) {
                $metadata['domain_warning'] = $domainWarning;
            }

            $this->appendProvisionLog($site, 'done', 'اكتمل الإنشاء — الموقع يعمل');

            $site->update([
                'status' => 'running',
                'public_url' => $resolvedPublic,
                'admin_url' => $adminUrl,
                'metadata' => $metadata,
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $this->appendProvisionLog($site, 'failed', $e->getMessage());

            if ($site->fresh()->status !== 'failed') {
                $site->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $service
     */
    protected function applyCloudflare(CoolifyWordpressSite $site, array $service): ?string
    {
        if (! $this->wordpressCloudflare->isEnabledForSite($site)) {
            $this->appendProvisionLog($site, 'cloudflare_skip', 'Cloudflare غير مفعّل لهذا الموقع');

            return null;
        }

        $preset = ($site->metadata ?? [])['security_preset'] ?? null;

        $result = $this->wordpressCloudflare->applyForSite(
            $site,
            $service,
            is_string($preset) ? $preset : null,
            fn (string $step, string $message) => $this->appendProvisionLog($site, $step, $message)
        );

        if ($result['ok'] ?? false) {
            return null;
        }

        return $result['message'] ?? 'فشل ربط Cloudflare';
    }

    protected function appendProvisionLog(CoolifyWordpressSite $site, string $step, string $message): void
    {
        $site->refresh();
        $metadata = $site->metadata ?? [];
        $log = $metadata['provision_log'] ?? [];
        $log[] = [
            'at' => now()->toIso8601String(),
            'step' => $step,
            'message' => $message,
        ];
        $metadata['provision_log'] = array_slice($log, -50);
        $metadata['provisioning_step'] = $step;
        $site->update(['metadata' => $metadata]);
    }

    /**
     * تحقق من جاهزية السيرفر والمشروع قبل إرسال الـ job.
     *
     * @return array{ok: bool, message?: string}
     */
    public function preflight(string $serverUuid, string $projectMode, ?string $projectUuid = null): array
    {
        $destination = $this->coolify->resolveServerDestinationUuid(
            $serverUuid,
            $this->settings->getWordpressDefaultDestinationUuid()
        );

        if ($destination === null || $destination === '') {
            $serverResponse = $this->coolify->getServer($serverUuid);
            if (! ($serverResponse['success'] ?? false)) {
                return ['ok' => false, 'message' => $serverResponse['message'] ?? 'تعذّر الوصول للسيرفر على Coolify'];
            }

            $server = $serverResponse['data'] ?? [];
            $destinations = is_array($server) ? $this->coolify->extractServerDestinations($server) : [];

            if (count($destinations) > 1) {
                return [
                    'ok' => false,
                    'message' => 'السيرفر له أكثر من destination. أدخل UUID الوجهة في إعدادات Coolify → مواقع WordPress.',
                ];
            }
        }

        if ($projectMode === 'shared') {
            $puuid = $projectUuid ?: $this->settings->getWordpressSharedProjectUuid();
            if ($puuid === '') {
                return ['ok' => false, 'message' => 'حدّد المشروع المشترك.'];
            }

            $envName = $this->settings->getWordpressDefaultEnvironment();
            $envs = $this->coolify->listProjectEnvironments($puuid);
            $found = false;
            foreach ($envs as $env) {
                if (strtolower((string) ($env['name'] ?? '')) === strtolower($envName)) {
                    $found = true;
                    break;
                }
            }
            if (! $found && $envs === []) {
                return ['ok' => false, 'message' => 'المشروع المشترك لا يحتوي بيئات. أنشئ بيئة production في Coolify أولاً.'];
            }
        }

        return ['ok' => true];
    }

    protected function resolveProjectUuid(CoolifyWordpressSite $site): string
    {
        if ($site->project_mode === 'shared') {
            $uuid = $site->project_uuid ?: $this->settings->getWordpressSharedProjectUuid();
            if ($uuid === '') {
                throw new \RuntimeException('لم يُحدَّد مشروع مشترك. اضبطه في إعدادات Coolify أو اختر مشروعاً في المعالج.');
            }

            $this->ensureProjectEnvironment($site, $uuid);

            return $uuid;
        }

        if ($site->project_uuid) {
            $this->ensureProjectEnvironment($site, $site->project_uuid);

            return $site->project_uuid;
        }

        $payload = [
            'name' => $this->coolifyProjectName($site),
            'description' => 'WordPress '.$site->slug,
        ];

        $response = $this->coolify->createProject($payload);

        if (! ($response['success'] ?? false)) {
            $this->failApi($site, 'create_project', $payload, $response);
        }

        $data = $response['data'] ?? [];
        $uuid = (string) ($data['uuid'] ?? '');
        if ($uuid === '') {
            throw new \RuntimeException('لم يُرجع Coolify معرف المشروع بعد الإنشاء');
        }

        $site->update(['project_name' => $site->display_name]);

        $this->ensureProjectEnvironment($site, $uuid);

        return $uuid;
    }

    protected function ensureProjectEnvironment(CoolifyWordpressSite $site, string $projectUuid): void
    {
        $envName = $site->environment_name ?: $this->settings->getWordpressDefaultEnvironment();
        $resolved = $this->coolify->resolveProjectEnvironment($projectUuid, $envName);

        $site->update([
            'environment_name' => $resolved['environment_name'],
        ]);

        $metadata = $site->metadata ?? [];
        $metadata['environment_uuid'] = $resolved['environment_uuid'];
        $site->update(['metadata' => $metadata]);
    }

    protected function coolifyProjectName(CoolifyWordpressSite $site): string
    {
        return Str::limit('wp-'.$site->slug, 255, '');
    }

    protected function coolifyServiceName(CoolifyWordpressSite $site): string
    {
        return Str::limit($site->slug, 255, '');
    }

    protected function createWordpressService(CoolifyWordpressSite $site, string $projectUuid, string $publicUrl): string
    {
        $envName = $site->environment_name ?: $this->settings->getWordpressDefaultEnvironment();
        $envUuid = ($site->metadata ?? [])['environment_uuid'] ?? null;

        $serviceType = $this->settings->getWordpressServiceType();

        $payload = [
            'type' => $serviceType,
            'name' => $this->coolifyServiceName($site),
            'project_uuid' => $projectUuid,
            'server_uuid' => $site->server_uuid,
            'environment_name' => $envName,
        ];

        if (filled($envUuid)) {
            $payload['environment_uuid'] = (string) $envUuid;
        }

        if (filled($site->description)) {
            $payload['description'] = Str::limit(
                preg_replace('/[^\p{L}\p{N}\s\-_.,!?()\'"+=*\/@&]/u', '', $site->description),
                255
            );
        }

        $destinationUuid = $this->coolify->resolveServerDestinationUuid(
            $site->server_uuid,
            $this->settings->getWordpressDefaultDestinationUuid()
        );

        if ($destinationUuid !== null && $destinationUuid !== '') {
            $payload['destination_uuid'] = $destinationUuid;
        }

        if ($this->settings->getWordpressInstantDeploy()) {
            $payload['instant_deploy'] = true;
        }

        $response = $this->coolify->createService($payload);

        if (! ($response['success'] ?? false)) {
            $this->failApi($site, 'create_service', $payload, $response);
        }

        $data = $response['data'] ?? [];
        $uuid = (string) ($data['uuid'] ?? '');
        if ($uuid === '') {
            throw new \RuntimeException('لم يُرجع Coolify معرف الخدمة');
        }

        $metadata = $site->metadata ?? [];
        $metadata['service_type'] = $serviceType;
        $site->update(['metadata' => $metadata]);

        $this->applyWordpressDockerEnv($site, $uuid);

        return $uuid;
    }

    protected function applyWordpressDockerEnv(CoolifyWordpressSite $site, string $serviceUuid): void
    {
        $tag = $this->settings->getWordpressDockerTag();
        if ($tag === '') {
            return;
        }

        $response = $this->coolify->createServiceEnv($serviceUuid, [
            'key' => 'WORDPRESS_VERSION',
            'value' => $tag,
            'is_preview' => false,
            'is_literal' => true,
        ]);

        if ($response['success'] ?? false) {
            $this->appendProvisionLog($site, 'wordpress_image', 'WORDPRESS_VERSION='.$tag);
        }
    }

    /**
     * @return string|null تحذير إن فشل تعيين النطاق (لا يُوقف الإنشاء)
     */
    protected function applyUrlsToService(string $serviceUuid, string $publicUrl): ?string
    {
        $patch = $this->coolify->updateService($serviceUuid, [
            'urls' => $this->coolify->buildServiceUrls($publicUrl),
            'force_domain_override' => true,
        ]);

        if ($patch['success'] ?? false) {
            return null;
        }

        $status = (int) ($patch['status'] ?? 0);
        $message = $patch['message'] ?? 'فشل تعيين النطاق على Coolify';

        return $message.' (HTTP '.$status.'). يمكنك ضبط النطاق يدوياً من Coolify.';
    }

    protected function triggerServiceDeploy(string $serviceUuid): void
    {
        $restart = $this->coolify->restartService($serviceUuid);
        if (! ($restart['success'] ?? false)) {
            $this->coolify->startService($serviceUuid);
        }

        sleep(self::INITIAL_RESTART_SLEEP_SECONDS);
    }

    protected function waitUntilRunning(CoolifyWordpressSite $site, string $serviceUuid): void
    {
        $runningStatuses = ['running', 'healthy', 'started', 'active'];
        $inProgressStatuses = [
            'starting', 'deploying', 'building', 'pulling', 'restarting', 'creating', 'created', 'stopping', '',
        ];
        $recoverableStatuses = ['exited', 'stopped'];
        $deployRetries = 0;
        $maxDeployRetries = 6;
        $exitedStreak = 0;
        $maxExitedStreak = 8;
        $startedAt = time();
        $lastLoggedSummary = '';

        $this->appendProvisionLog($site, 'wait_containers', 'انتظار تشغيل mariadb وwordpress...');

        for ($i = 0; $i < self::MAX_POLL_ATTEMPTS; $i++) {
            $service = $this->fetchService($serviceUuid);
            $status = strtolower((string) ($service['status'] ?? ''));
            $components = $this->coolify->extractServiceComponentStatuses($service);
            $elapsed = time() - $startedAt;
            $inGrace = $elapsed < self::DEPLOY_GRACE_SECONDS;

            $componentSummary = collect($components)
                ->map(fn (array $c) => ($c['name'] ?? '?').':'.($c['status'] ?? '?'))
                ->implode(', ');

            $site->update([
                'metadata' => array_merge($site->metadata ?? [], [
                    'coolify_service_status' => $status,
                    'coolify_components' => $components,
                ]),
            ]);

            if ($componentSummary !== $lastLoggedSummary && ($i % 2 === 0 || $i < 3)) {
                $lastLoggedSummary = $componentSummary;
                $graceLabel = $inGrace ? ' (مهلة أولية)' : '';
                $this->appendProvisionLog(
                    $site,
                    'wait_containers',
                    'Coolify: '.$status.' | '.$componentSummary.$graceLabel
                );
            }

            if ($this->coolify->isServiceStackHealthy($service) || in_array($status, $runningStatuses, true)) {
                return;
            }

            if (in_array($status, ['failed', 'error', 'unhealthy'], true) && ! $inGrace) {
                $this->failDeployment($site, $serviceUuid, $status, $service);
            }

            if (in_array($status, $recoverableStatuses, true) || ! $this->coolify->isServiceStackHealthy($service)) {
                if ($inGrace && $deployRetries < $maxDeployRetries && ($i % 4 === 0)) {
                    $deployRetries++;
                    $this->appendProvisionLog($site, 'deploy', 'إعادة تشغيل الخدمة (محاولة '.$deployRetries.')');
                    $this->coolify->restartService($serviceUuid);
                    sleep(10);

                    continue;
                }

                if (in_array($status, $recoverableStatuses, true)) {
                    $exitedStreak++;
                } else {
                    $exitedStreak = 0;
                }

                if (! $inGrace && $exitedStreak >= $maxExitedStreak) {
                    $this->failDeployment($site, $serviceUuid, $status, $service);
                }
            } else {
                $exitedStreak = 0;
            }

            if (! in_array($status, array_merge($inProgressStatuses, $recoverableStatuses, $runningStatuses), true) && $status !== '') {
                // حالة غير معروفة — نتابع الانتظار
            }

            sleep(self::POLL_INTERVAL_SECONDS);
        }

        throw new \RuntimeException('انتهت مهلة انتظار تشغيل الخدمة (~'.(self::MAX_POLL_ATTEMPTS * self::POLL_INTERVAL_SECONDS / 60).' دقيقة). راجع سجلات mariadb وwordpress في Coolify.');
    }

    /**
     * @param  array<string, mixed>  $service
     */
    protected function failDeployment(CoolifyWordpressSite $site, string $serviceUuid, string $status, array $service): void
    {
        $components = $this->coolify->extractServiceComponentStatuses($service);
        $componentSummary = collect($components)
            ->map(fn (array $c) => ($c['name'] ?? '?').': '.($c['status'] ?? '?'))
            ->implode(', ');

        $hint = 'الحاويات متوقفة. في Coolify افتح الخدمة → Logs (غالباً mariadb يفشل أولاً).';
        if ($componentSummary !== '') {
            $hint .= ' الحالة: '.$componentSummary.'.';
        }
        $hint .= ' جرّب «إعادة تشغيل على Coolify» من هذه الصفحة.';

        $site->update([
            'metadata' => array_merge($site->metadata ?? [], [
                'coolify_service_status' => $status,
                'coolify_components' => $components,
                'last_service_snapshot' => Arr::only($service, ['uuid', 'name', 'status', 'type', 'fqdn']),
            ]),
        ]);

        throw new \RuntimeException('فشل نشر الخدمة على Coolify (الحالة: '.$status.'). '.$hint);
    }

    /**
     * @return array<string, mixed>
     */
    protected function fetchService(string $serviceUuid): array
    {
        $response = $this->coolify->getService($serviceUuid);
        if (! ($response['success'] ?? false)) {
            throw new \RuntimeException($response['message'] ?? 'فشل جلب بيانات الخدمة');
        }

        $data = $response['data'] ?? [];

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $response
     */
    protected function failApi(CoolifyWordpressSite $site, string $step, array $payload, array $response): void
    {
        $message = (string) ($response['message'] ?? 'فشل طلب Coolify');
        $status = (int) ($response['status'] ?? 0);

        $site->update([
            'status' => 'failed',
            'error_message' => $message,
            'metadata' => array_merge($site->metadata ?? [], [
                'last_api' => [
                    'step' => $step,
                    'payload' => $payload,
                    'http_status' => $status,
                    'body' => $response['data'] ?? $response,
                ],
            ]),
        ]);

        throw new \RuntimeException($message);
    }

    public function syncSiteFromCoolify(CoolifyWordpressSite $site): void
    {
        if (! $site->service_uuid) {
            return;
        }

        $service = $this->fetchService($site->service_uuid);
        $envs = $this->coolify->normalizeList($this->coolify->listServiceEnvs($site->service_uuid)['data'] ?? []);
        $public = $this->coolify->extractServicePublicUrl($service) ?: $site->public_url;
        $status = strtolower((string) ($service['status'] ?? ''));
        $stackHealthy = $this->coolify->isServiceStackHealthy($service);
        $runningStatuses = ['running', 'healthy', 'started', 'active'];

        $siteStatus = $site->status;
        if ($stackHealthy || in_array($status, $runningStatuses, true)) {
            $siteStatus = 'running';
        }

        $metadata = array_merge($site->metadata ?? [], [
            'service' => Arr::only($service, ['uuid', 'name', 'status', 'type', 'fqdn', 'domains']),
            'database_env' => $this->coolify->extractDatabaseEnvFromServiceEnvs($envs),
            'coolify_service_status' => $status,
            'coolify_components' => $this->coolify->extractServiceComponentStatuses($service),
            'coolify_stack_healthy' => $stackHealthy,
            'synced_at' => now()->toIso8601String(),
        ]);

        if ($stackHealthy && in_array($site->status, ['provisioning', 'pending'], true)) {
            $metadata['provisioning_step'] = 'done';
        }

        $updates = [
            'status' => $siteStatus,
            'public_url' => $public,
            'admin_url' => $this->coolify->extractWordpressAdminUrl($service, $envs),
            'metadata' => $metadata,
        ];

        if ($stackHealthy) {
            $updates['error_message'] = null;
        }

        $site->update($updates);
    }

    public function updateSiteDomain(CoolifyWordpressSite $site, string $publicUrl): void
    {
        if (! $site->service_uuid) {
            throw new \RuntimeException('الخدمة غير موجودة بعد');
        }

        $response = $this->coolify->updateService($site->service_uuid, [
            'urls' => $this->coolify->buildServiceUrls($publicUrl),
            'force_domain_override' => true,
        ]);

        if (! ($response['success'] ?? false)) {
            throw new \RuntimeException($response['message'] ?? 'فشل تحديث النطاق على Coolify');
        }

        $site->update([
            'public_url' => $publicUrl,
            'admin_url' => rtrim($publicUrl, '/').'/wp-admin',
        ]);

        $this->syncSiteFromCoolify($site);
    }
}
