<?php

namespace App\Services\Client;

use App\Models\ClientCoolifyProject;
use App\Models\ClientDomain;
use App\Models\User;
use App\Models\WhmAccount;
use App\Services\Coolify\CoolifyTeamService;
use App\Services\CoolifyApiService;
use App\Services\Domain\DomainCommandCenterService;
use Illuminate\Support\Collection;

class ClientAssetService
{
    public function __construct(
        protected DomainCommandCenterService $domainCenter,
        protected CoolifyApiService $coolify,
        protected CoolifyTeamService $teamService
    ) {}

    /**
     * @return array<string, int|null>
     */
    public function domainOwnershipMap(): array
    {
        $map = ClientDomain::query()
            ->whereNotNull('user_id')
            ->pluck('user_id', 'domain_name')
            ->all();

        foreach (WhmAccount::query()->whereNotNull('user_id')->get(['domain', 'user_id']) as $account) {
            $name = ClientDomain::normalizeName((string) $account->domain);
            if ($name !== '' && ! isset($map[$name])) {
                $map[$name] = $account->user_id;
            }
        }

        return $map;
    }

    /**
     * @return array<string, ClientCoolifyProject>
     */
    public function coolifyProjectAssignmentMap(): array
    {
        return ClientCoolifyProject::query()
            ->with('client')
            ->get()
            ->keyBy('project_uuid')
            ->all();
    }

    /**
     * @return array{success: bool, message: string, domain?: ClientDomain}
     */
    public function assignDomain(?int $userId, string $domainName): array
    {
        $name = ClientDomain::normalizeName($domainName);
        if ($name === '') {
            return ['success' => false, 'message' => 'اسم النطاق غير صالح'];
        }

        if ($userId !== null && ! User::whereKey($userId)->exists()) {
            return ['success' => false, 'message' => 'المستخدم غير موجود'];
        }

        $record = ClientDomain::updateOrCreate(
            ['domain_name' => $name],
            ['user_id' => $userId]
        );

        return [
            'success' => true,
            'message' => $userId ? 'تم ربط النطاق بالعميل' : 'تم إلغاء ربط النطاق',
            'domain' => $record->fresh()->load('client'),
        ];
    }

    /**
     * @return array{success: bool, message: string, project?: ClientCoolifyProject}
     */
    public function assignCoolifyProject(?int $userId, string $uuid, ?string $name = null): array
    {
        $uuid = trim($uuid);
        if ($uuid === '') {
            return ['success' => false, 'message' => 'معرّف المشروع غير صالح'];
        }

        if ($userId !== null && ! User::whereKey($userId)->exists()) {
            return ['success' => false, 'message' => 'المستخدم غير موجود'];
        }

        $coolifyTeamId = null;

        if ($userId !== null) {
            $assert = $this->teamService->assertProjectInClientTeam($userId, $uuid);
            if (! $assert['success']) {
                return ['success' => false, 'message' => $assert['message']];
            }

            $link = $this->teamService->teamForUser($userId);
            $coolifyTeamId = $link?->coolify_team_id;
        }

        $record = ClientCoolifyProject::updateOrCreate(
            ['project_uuid' => $uuid],
            [
                'user_id' => $userId,
                'coolify_team_id' => $coolifyTeamId,
                'project_name' => $name,
            ]
        );

        return [
            'success' => true,
            'message' => $userId ? 'تم ربط المشروع بالعميل' : 'تم إلغاء ربط المشروع',
            'project' => $record->fresh()->load('client'),
        ];
    }

    public function userOwnsDomain(int $userId, string $domainName): bool
    {
        $name = ClientDomain::normalizeName($domainName);
        $map = $this->domainOwnershipMap();

        return isset($map[$name]) && (int) $map[$name] === $userId;
    }

    public function userOwnsCoolifyProject(int $userId, string $uuid): bool
    {
        return ClientCoolifyProject::query()
            ->where('project_uuid', $uuid)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function domainsForUser(int $userId, bool $forceRefresh = false): Collection
    {
        $payload = $this->domainCenter->build($forceRefresh);
        $this->domainCenter->attachClientOwnership($payload['rows']);

        return collect($payload['rows'])->filter(
            fn (array $row) => (int) ($row['user_id'] ?? 0) === $userId
        )->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function domainDetailForUser(User $user, string $domainName, bool $forceRefresh = false): ?array
    {
        $name = ClientDomain::normalizeName($domainName);
        if (! $this->userOwnsDomain($user->id, $name)) {
            return null;
        }

        $row = $this->domainsForUser($user->id, $forceRefresh)->firstWhere('name', $name);

        return $row ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function coolifyProjectsForUser(int $userId): array
    {
        $assignments = ClientCoolifyProject::query()
            ->where('user_id', $userId)
            ->get();

        if ($assignments->isEmpty()) {
            return [];
        }

        $api = $this->teamService->apiForUser($userId) ?? $this->coolify;
        $projects = [];

        if ($api->isConfigured()) {
            $response = $api->listProjects();
            if ($response['success'] ?? false) {
                foreach ($api->normalizeList($response['data'] ?? []) as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $uuid = (string) ($item['uuid'] ?? '');
                    if ($uuid !== '') {
                        $projects[$uuid] = $item;
                    }
                }
            }
        }

        $result = [];
        foreach ($assignments as $assignment) {
            $uuid = $assignment->project_uuid;
            $apiProject = $projects[$uuid] ?? null;
            $result[] = [
                'uuid' => $uuid,
                'name' => $apiProject['name'] ?? $assignment->project_name ?? $uuid,
                'description' => $apiProject['description'] ?? null,
                'assignment' => $assignment,
                'api' => $apiProject,
            ];
        }

        return $result;
    }

    /**
     * @return array{project: array<string, mixed>|null, resources: array<int, array<string, mixed>>, inspection: array<string, mixed>}
     */
    public function coolifyProjectDetailForUser(User $user, string $uuid): array
    {
        if (! $this->userOwnsCoolifyProject($user->id, $uuid)) {
            return ['project' => null, 'resources' => [], 'inspection' => []];
        }

        $api = $this->teamService->apiForUser($user->id) ?? $this->coolify;

        $project = null;
        $resources = [];
        if ($api->isConfigured()) {
            $projectRes = $api->getProject($uuid);
            if ($projectRes['success'] ?? false) {
                $data = $projectRes['data'] ?? [];
                $project = is_array($data) && isset($data[0]) ? $data[0] : $data;
            }
            $resourcesRes = $api->projectResources($uuid);
            if ($resourcesRes['success'] ?? false) {
                $resources = $api->normalizeList($resourcesRes['data'] ?? []);
            }
        }

        return [
            'project' => is_array($project) ? $project : null,
            'resources' => $resources,
            'inspection' => [],
        ];
    }

    /**
     * @return array{domains: int, projects: int, hosting: int, team_linked: bool}
     */
    public function portalSummary(int $userId): array
    {
        $team = $this->teamService->teamForUser($userId);

        return [
            'domains' => $this->domainsForUser($userId)->count(),
            'projects' => ClientCoolifyProject::where('user_id', $userId)->count(),
            'hosting' => WhmAccount::where('user_id', $userId)->where('status', '!=', 'terminated')->count(),
            'team_linked' => $team !== null && $team->hasApiToken(),
        ];
    }
}
