<?php

namespace App\Services\Coolify;

use App\Services\CoolifyApiService;

class CoolifyProjectBackupPlanner
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifySettingsService $settings
    ) {}

    /**
     * @param  array{
     *     scope: string,
     *     project_uuid?: string|null,
     *     server_uuid?: string|null,
     *     resource_uuids?: array<int, string>,
     *     include_databases?: bool,
     *     include_applications?: bool,
     *     include_services?: bool
     * }  $input
     * @return array<int, array<string, mixed>>
     */
    public function buildPlan(array $input): array
    {
        $scope = $input['scope'] ?? 'single_project';
        $includeDb = $input['include_databases'] ?? true;
        $includeApps = $input['include_applications'] ?? true;
        $includeServices = $input['include_services'] ?? true;
        $selectedUuids = $input['resource_uuids'] ?? [];

        $resources = $this->collectResources(
            $scope,
            $input['project_uuid'] ?? null,
            $input['server_uuid'] ?? null
        );

        if ($scope === 'custom' && $selectedUuids !== []) {
            $resources = array_values(array_filter(
                $resources,
                fn (array $r) => in_array($r['uuid'] ?? '', $selectedUuids, true)
            ));
        }

        $plan = [];

        foreach ($resources as $resource) {
            $type = strtolower((string) ($resource['type'] ?? $resource['resource_type'] ?? ''));
            $uuid = (string) ($resource['uuid'] ?? '');

            if ($uuid === '') {
                continue;
            }

            if (str_contains($type, 'database') && ! $includeDb) {
                continue;
            }
            if (str_contains($type, 'application') && ! $includeApps) {
                continue;
            }
            if (str_contains($type, 'service') && ! $includeServices) {
                continue;
            }

            $server = $this->coolify->resolveResourceServer($resource);
            $strategy = $this->resolveStrategy($type, $uuid);

            $plan[] = [
                'resource_type' => $this->normalizeResourceType($type),
                'resource_uuid' => $uuid,
                'resource_name' => $resource['name'] ?? $uuid,
                'project_uuid' => $resource['project_uuid'] ?? $input['project_uuid'] ?? null,
                'project_name' => $resource['project_name'] ?? null,
                'server_uuid' => $server['server_uuid'] ?? null,
                'server_host' => $server['host'] ?? null,
                'strategy' => $strategy,
                'enabled' => true,
                'volume_count' => $strategy === 'ssh_volume' ? $this->countVolumes($uuid) : 0,
            ];
        }

        return $plan;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function collectResources(string $scope, ?string $projectUuid, ?string $serverUuid = null): array
    {
        if ($scope === 'server' && $serverUuid) {
            return $this->collectServerResources($serverUuid);
        }

        if ($scope === 'all_projects') {
            $all = [];
            $projectsResponse = $this->coolify->listProjects();
            $projects = $this->coolify->normalizeList($projectsResponse['data'] ?? []);

            foreach ($projects as $project) {
                $puuid = (string) ($project['uuid'] ?? '');
                if ($puuid === '') {
                    continue;
                }
                foreach ($this->projectResources($puuid, $project['name'] ?? null) as $r) {
                    $all[] = $r;
                }
            }

            return $all;
        }

        if ($projectUuid) {
            return $this->projectResources($projectUuid);
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function collectServerResources(string $serverUuid): array
    {
        $all = [];
        $projectsResponse = $this->coolify->listProjects();
        $projects = $this->coolify->normalizeList($projectsResponse['data'] ?? []);

        foreach ($projects as $project) {
            $puuid = (string) ($project['uuid'] ?? '');
            if ($puuid === '') {
                continue;
            }
            foreach ($this->projectResources($puuid, $project['name'] ?? null) as $resource) {
                $resourceServer = $this->coolify->extractResourceServerUuid($resource);
                if ($resourceServer === $serverUuid) {
                    $all[] = $resource;
                }
            }
        }

        return $all;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function projectResources(string $projectUuid, ?string $projectName = null): array
    {
        $response = $this->coolify->projectResources($projectUuid);
        $items = $this->coolify->normalizeList($response['data'] ?? []);

        return array_map(function (array $item) use ($projectUuid, $projectName) {
            $item['project_uuid'] = $projectUuid;
            $item['project_name'] = $projectName;

            return $item;
        }, $items);
    }

    protected function resolveStrategy(string $type, string $uuid): string
    {
        if (str_contains($type, 'database')) {
            return $this->settings->getCoolifyS3StorageUuid() !== ''
                ? 'coolify_api'
                : 'manifest_only';
        }

        if (str_contains($type, 'application') || str_contains($type, 'service')) {
            $count = $this->countVolumes($uuid, $type);

            return $count > 0 ? 'ssh_volume' : 'manifest_only';
        }

        return 'manifest_only';
    }

    protected function countVolumes(string $uuid, ?string $type = null): int
    {
        if ($type === null || str_contains(strtolower($type), 'application')) {
            $response = $this->coolify->listApplicationStorages($uuid);
            if ($response['success'] ?? false) {
                return count($this->coolify->normalizeList($response['data'] ?? []));
            }
        }

        return 0;
    }

    protected function normalizeResourceType(string $type): string
    {
        if (str_contains($type, 'database')) {
            return 'database';
        }
        if (str_contains($type, 'application')) {
            return 'application';
        }
        if (str_contains($type, 'service')) {
            return 'service';
        }

        return 'resource';
    }
}
