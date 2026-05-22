<?php

namespace App\Services\Coolify;

use App\Models\ClientCoolifyTeam;
use App\Models\User;
use App\Services\CoolifyApiService;
use Illuminate\Support\Collection;

class CoolifyTeamService
{
    public function __construct(
        protected CoolifyApiService $coolify
    ) {}

    /**
     * @return array<int, ClientCoolifyTeam>
     */
    public function teamLinksByCoolifyId(): array
    {
        return ClientCoolifyTeam::query()
            ->with('client')
            ->get()
            ->keyBy('coolify_team_id')
            ->all();
    }

    public function teamForUser(int $userId): ?ClientCoolifyTeam
    {
        return ClientCoolifyTeam::query()
            ->where('user_id', $userId)
            ->first();
    }

    public function apiForUser(int $userId): ?CoolifyApiService
    {
        $link = $this->teamForUser($userId);
        if ($link === null || ! $link->hasApiToken()) {
            return null;
        }

        $api = $this->coolify->withToken($link->getDecryptedApiToken());

        return $api->isConfigured() ? $api : null;
    }

    /**
     * @return array{success: bool, message: string, team?: ClientCoolifyTeam}
     */
    public function linkTeamToUser(
        User $user,
        int $coolifyTeamId,
        ?string $teamName = null,
        ?string $apiToken = null,
        ?string $notes = null
    ): array {
        if ($coolifyTeamId <= 0) {
            return ['success' => false, 'message' => 'معرّف الفريق غير صالح'];
        }

        $existingForTeam = ClientCoolifyTeam::query()
            ->where('coolify_team_id', $coolifyTeamId)
            ->where('user_id', '!=', $user->id)
            ->exists();

        if ($existingForTeam) {
            return ['success' => false, 'message' => 'هذا الفريق مربوط بعميل آخر'];
        }

        if ($apiToken !== null && $apiToken !== '') {
            $validation = $this->validateTeamToken($coolifyTeamId, $apiToken);
            if (! $validation['success']) {
                return $validation;
            }
            $teamName = $teamName ?: ($validation['team_name'] ?? null);
        }

        $record = ClientCoolifyTeam::updateOrCreate(
            ['user_id' => $user->id],
            [
                'coolify_team_id' => $coolifyTeamId,
                'team_name' => $teamName,
                'notes' => $notes,
            ]
        );

        if ($apiToken !== null && $apiToken !== '') {
            $record->api_token = $apiToken;
            $record->save();
        }

        return [
            'success' => true,
            'message' => 'تم ربط فريق Coolify بالعميل',
            'team' => $record->fresh()->load('client'),
        ];
    }

    /**
     * @return array{success: bool, message: string, team_name?: string, team_id?: int}
     */
    public function validateTeamToken(int $expectedTeamId, string $token): array
    {
        if (! $this->coolify->isConfigured()) {
            return ['success' => false, 'message' => 'إعدادات Coolify غير مكتملة'];
        }

        $api = $this->coolify->withToken($token);
        if (! $api->isConfigured()) {
            return ['success' => false, 'message' => 'عنوان API غير مضبوط'];
        }

        $response = $api->getCurrentTeam();
        if (! ($response['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $response['message'] ?? 'فشل التحقق من التوكن',
            ];
        }

        $team = $response['data'] ?? [];
        if (! is_array($team)) {
            return ['success' => false, 'message' => 'استجابة غير متوقعة من Coolify'];
        }

        $teamId = (int) ($team['id'] ?? 0);
        if ($teamId !== $expectedTeamId) {
            return [
                'success' => false,
                'message' => "التوكن مربوط بالفريق #{$teamId} وليس #{$expectedTeamId}",
            ];
        }

        return [
            'success' => true,
            'message' => 'التوكن صالح للفريق',
            'team_name' => (string) ($team['name'] ?? ''),
            'team_id' => $teamId,
        ];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function unlinkTeam(User $user): array
    {
        $deleted = ClientCoolifyTeam::query()->where('user_id', $user->id)->delete();

        return [
            'success' => $deleted > 0,
            'message' => $deleted > 0 ? 'تم إلغاء ربط الفريق' : 'لا يوجد ربط فريق لهذا العميل',
        ];
    }

    /**
     * @return array{team: array<string, mixed>|null, members: array<int, array<string, mixed>>}
     */
    public function syncTeamMetadata(int $userId): array
    {
        $link = $this->teamForUser($userId);
        if ($link === null) {
            return ['team' => null, 'members' => []];
        }

        $api = $this->coolify;
        if ($link->hasApiToken()) {
            $api = $this->coolify->withToken($link->getDecryptedApiToken());
        }

        $team = null;
        $members = [];

        if ($api->isConfigured()) {
            $teamRes = $api->getTeam($link->coolify_team_id);
            if ($teamRes['success'] ?? false) {
                $data = $teamRes['data'] ?? [];
                $team = is_array($data) ? $data : null;
                if ($team && empty($link->team_name)) {
                    $link->update(['team_name' => (string) ($team['name'] ?? '')]);
                }
            }

            $membersRes = $api->getTeamMembers($link->coolify_team_id);
            if ($membersRes['success'] ?? false) {
                $members = $api->normalizeList($membersRes['data'] ?? []);
            }
        }

        return ['team' => $team, 'members' => $members];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function projectsForTeam(ClientCoolifyTeam $link): Collection
    {
        $api = $link->hasApiToken()
            ? $this->coolify->withToken($link->getDecryptedApiToken())
            : $this->coolify;

        if (! $api->isConfigured()) {
            return collect();
        }

        $response = $api->listProjects();
        if (! ($response['success'] ?? false)) {
            return collect();
        }

        return collect($api->normalizeList($response['data'] ?? []));
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function assertProjectInClientTeam(int $userId, string $projectUuid): array
    {
        $link = $this->teamForUser($userId);
        if ($link === null) {
            return [
                'success' => false,
                'message' => 'اربط فريق Coolify وتوكنه للعميل أولاً من قسم فرق العمل',
            ];
        }

        if (! $link->hasApiToken()) {
            return [
                'success' => false,
                'message' => 'أضف توكن API الخاص بفريق العميل للتحقق من المشروع',
            ];
        }

        $projects = $this->projectsForTeam($link);
        $found = $projects->contains(fn (array $p) => ($p['uuid'] ?? '') === $projectUuid);

        if (! $found) {
            return [
                'success' => false,
                'message' => 'المشروع غير موجود ضمن فريق Coolify الخاص بهذا العميل',
            ];
        }

        return ['success' => true, 'message' => 'المشروع ضمن فريق العميل'];
    }
}
