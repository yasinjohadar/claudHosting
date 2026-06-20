<?php

namespace App\Services\Coolify;

use App\Models\CoolifyProjectSnapshot;
use App\Models\CoolifyWordpressSite;
use App\Services\CoolifyApiService;
use Illuminate\Support\Facades\Cache;

class CoolifyOperationsService
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifySettingsService $settings,
        protected CoolifySshExecutor $ssh,
        protected CoolifyProjectCleanupService $projectCleanup,
        protected DockerHostService $dockerHost
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(bool $refreshSsh = false): array
    {
        $configured = $this->coolify->isConfigured();
        $connected = $configured && $this->coolify->ping();

        $unhealthyResources = [];
        $runningDeployments = [];
        $failedDeployments = [];
        $wordpressIssues = [];
        $failedSnapshots = [];
        $sshStatuses = [];
        $systemHealth = null;
        $systemVersion = null;
        $dockerInfrastructure = [];
        $wordpressDockerHealth = ['checked' => 0, 'unhealthy_count' => 0, 'unhealthy' => []];

        if ($connected) {
            $unhealthyResources = $this->collectUnhealthyResources();
            [$runningDeployments, $failedDeployments] = $this->collectDeployments();
            $systemHealth = $this->coolify->getHealth();
            $versionResponse = $this->coolify->getVersion();
            $systemVersion = $versionResponse['data'] ?? $versionResponse['message'] ?? null;
            $dockerInfrastructure = $this->collectDockerInfrastructure();
            $wordpressDockerHealth = $this->dockerHost->summarizeWordpressSitesHealth();
        }

        $wordpressIssues = CoolifyWordpressSite::query()
            ->whereIn('status', ['failed', 'provisioning', 'pending'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (CoolifyWordpressSite $s) => [
                'uuid' => $s->uuid,
                'name' => $s->display_name,
                'slug' => $s->slug,
                'status' => $s->status,
                'status_label' => CoolifyWordpressSite::STATUSES[$s->status] ?? $s->status,
                'error' => $s->error_message,
                'url' => route('admin.coolify.wordpress-sites.show', $s->uuid),
            ])
            ->all();

        $failedSnapshots = CoolifyProjectSnapshot::query()
            ->whereIn('status', ['failed', 'partial'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (CoolifyProjectSnapshot $s) => [
                'uuid' => $s->uuid,
                'name' => $s->name,
                'project_name' => $s->project_name,
                'status' => $s->status,
                'status_label' => CoolifyProjectSnapshot::STATUSES[$s->status] ?? $s->status,
                'completed_at' => $s->completed_at?->format('Y-m-d H:i'),
                'url' => route('admin.coolify.backups.snapshots.show', $s->uuid),
            ])
            ->all();

        if ($configured) {
            $sshStatuses = $this->collectSshStatuses($refreshSsh);
        }

        return [
            'configured' => $configured,
            'connected' => $connected,
            'stats' => $this->coolify->getDashboardStats(),
            'unhealthy_resources' => $unhealthyResources,
            'running_deployments' => $runningDeployments,
            'failed_deployments' => $failedDeployments,
            'wordpress_issues' => $wordpressIssues,
            'failed_snapshots' => $failedSnapshots,
            'ssh_statuses' => $sshStatuses,
            'system_health' => $systemHealth,
            'system_version' => $systemVersion,
            'docker_infrastructure' => $dockerInfrastructure,
            'wordpress_docker_health' => $wordpressDockerHealth,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function collectDockerInfrastructure(): array
    {
        $out = [];
        $servers = $this->coolify->normalizeList($this->coolify->listServers()['data'] ?? []);
        foreach ($servers as $server) {
            if (! is_array($server)) {
                continue;
            }
            $uuid = (string) ($server['uuid'] ?? '');
            if ($uuid === '') {
                continue;
            }
            $summary = $this->dockerHost->collectInfrastructureSummary($uuid);
            $summary['server_name'] = $server['name'] ?? $uuid;
            $summary['url'] = route('admin.coolify.servers.show', $uuid);
            $out[] = $summary;
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function collectUnhealthyResources(): array
    {
        $out = [];
        $lists = [
            ['type' => 'application', 'label' => 'تطبيق', 'fn' => fn () => $this->coolify->listApplications()],
            ['type' => 'service', 'label' => 'خدمة', 'fn' => fn () => $this->coolify->listServices()],
            ['type' => 'database', 'label' => 'قاعدة بيانات', 'fn' => fn () => $this->coolify->listDatabases()],
        ];

        foreach ($lists as $spec) {
            $response = $spec['fn']();
            if (! ($response['success'] ?? false)) {
                continue;
            }
            foreach ($this->coolify->normalizeList($response['data'] ?? []) as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $status = strtolower((string) ($item['status'] ?? $item['health'] ?? ''));
                $running = $this->coolify->isComponentStatusRunning($status);
                if ($running && ! in_array($status, ['unhealthy', 'failed', 'error', 'exited', 'stopped'], true)) {
                    continue;
                }
                if (! $running && in_array($status, ['', 'running', 'healthy', 'active', 'started'], true)) {
                    continue;
                }
                $uuid = (string) ($item['uuid'] ?? '');
                if ($uuid === '') {
                    continue;
                }
                $out[] = [
                    'type' => $spec['type'],
                    'type_label' => $spec['label'],
                    'name' => $item['name'] ?? $uuid,
                    'uuid' => $uuid,
                    'status' => $status ?: ($running ? 'running' : 'stopped'),
                    'url' => match ($spec['type']) {
                        'application' => route('admin.coolify.applications.show', $uuid),
                        'service' => route('admin.coolify.services.show', $uuid),
                        default => route('admin.coolify.databases.show', $uuid),
                    },
                ];
            }
        }

        return array_slice($out, 0, 30);
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    protected function collectDeployments(): array
    {
        $all = $this->coolify->normalizeList($this->coolify->listDeployments()['data'] ?? []);
        $running = [];
        $failed = [];

        foreach ($all as $d) {
            if (! is_array($d)) {
                continue;
            }
            $status = strtolower((string) ($d['status'] ?? ''));
            $uuid = (string) ($d['deployment_uuid'] ?? $d['uuid'] ?? '');
            $appUuid = (string) ($d['application_uuid'] ?? $d['resource_uuid'] ?? '');
            $row = [
                'uuid' => $uuid,
                'status' => $status,
                'application_name' => $d['application_name'] ?? $d['name'] ?? '—',
                'application_uuid' => $appUuid,
                'created_at' => $d['created_at'] ?? $d['started_at'] ?? null,
                'url' => $appUuid !== '' ? route('admin.coolify.deployments.show', $uuid ?: $appUuid) : route('admin.coolify.deployments.index'),
            ];
            if (in_array($status, ['in_progress', 'running', 'queued', 'deploying'], true)) {
                $running[] = $row;
            }
            if (in_array($status, ['failed', 'error', 'cancelled'], true)) {
                $failed[] = $row;
            }
        }

        return [array_slice($running, 0, 15), array_slice($failed, 0, 15)];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function collectSshStatuses(bool $refresh): array
    {
        $cacheKey = 'coolify_ops_ssh_status';
        if (! $refresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey, []);
        }

        $servers = $this->coolify->normalizeList($this->coolify->listServers()['data'] ?? []);
        $host = $this->settings->getSshHostFallback();
        $out = [];

        foreach ($servers as $server) {
            if (! is_array($server)) {
                continue;
            }
            $uuid = (string) ($server['uuid'] ?? '');
            $name = (string) ($server['name'] ?? $uuid);
            $ip = trim((string) ($server['ip'] ?? $server['host'] ?? ''));
            $target = $ip !== '' ? $ip : $host;
            $ok = false;
            $message = '—';
            if ($target !== '') {
                $test = $this->ssh->testConnection($target);
                $ok = (bool) ($test['success'] ?? false);
                $message = (string) ($test['message'] ?? '');
            } else {
                $message = 'لا يوجد عنوان IP';
            }
            $out[] = [
                'uuid' => $uuid,
                'name' => $name,
                'host' => $target,
                'ok' => $ok,
                'message' => $message,
                'url' => $uuid !== '' ? route('admin.coolify.servers.show', $uuid) : null,
            ];
        }

        Cache::put($cacheKey, $out, 120);

        return $out;
    }
}
