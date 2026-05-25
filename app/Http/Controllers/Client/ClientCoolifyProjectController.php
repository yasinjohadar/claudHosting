<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientCoolifyProject;
use App\Services\Coolify\CoolifyTeamService;
use App\Services\CoolifyApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientCoolifyProjectController extends Controller
{
    public function __construct(
        protected CoolifyTeamService $teamService,
        protected CoolifyApiService $coolify
    ) {
        $this->middleware('auth');
    }

    public function show(string $uuid): View|RedirectResponse
    {
        $user = auth()->user();
        $access = $this->assertAccess($user->id, $uuid);
        if (! $access['success']) {
            abort(403, $access['message']);
        }

        $api = $this->teamService->apiForUser($user->id) ?? $this->coolify;
        $projectRes = $api->getProject($uuid);
        $project = is_array($projectRes['data'] ?? null) ? $projectRes['data'] : null;

        if (! $project) {
            return redirect()->route('client.services')->with('error', 'المشروع غير موجود أو غير متاح');
        }

        $resources = $api->normalizeList($api->projectResources($uuid)['data'] ?? []);
        $applications = array_values(array_filter($resources, fn ($r) => in_array($r['type'] ?? '', ['application', 'applications'], true) || isset($r['git_repository'])));
        $services = array_values(array_filter($resources, fn ($r) => ($r['type'] ?? '') === 'service'));
        $databases = array_values(array_filter($resources, fn ($r) => ($r['type'] ?? '') === 'database'));

        $actions = config('coolify.client_portal.actions', []);

        return view('client.pages.coolify-project', compact(
            'project',
            'uuid',
            'applications',
            'services',
            'databases',
            'actions'
        ));
    }

    public function deployApplication(Request $request, string $uuid, string $appUuid): RedirectResponse
    {
        if (! config('coolify.client_portal.actions.deploy', true)) {
            abort(403);
        }

        $user = auth()->user();
        $access = $this->assertAccess($user->id, $uuid);
        if (! $access['success']) {
            abort(403, $access['message']);
        }

        $api = $this->teamService->apiForUser($user->id) ?? $this->coolify;
        $response = $api->deploy($appUuid);

        return back()->with(
            ($response['success'] ?? false) ? 'success' : 'error',
            ($response['success'] ?? false) ? 'تم بدء النشر' : ($response['message'] ?? 'فشل النشر')
        );
    }

    public function restartApplication(string $uuid, string $appUuid): RedirectResponse
    {
        if (! config('coolify.client_portal.actions.restart', false)) {
            abort(403);
        }

        $user = auth()->user();
        $access = $this->assertAccess($user->id, $uuid);
        if (! $access['success']) {
            abort(403, $access['message']);
        }

        $api = $this->teamService->apiForUser($user->id) ?? $this->coolify;
        $response = $api->restartApplication($appUuid);

        return back()->with(
            ($response['success'] ?? false) ? 'success' : 'error',
            ($response['success'] ?? false) ? 'تم إعادة التشغيل' : ($response['message'] ?? 'فشل الإجراء')
        );
    }

    public function applicationLogs(string $uuid, string $appUuid): JsonResponse
    {
        if (! config('coolify.client_portal.actions.view_logs', true)) {
            abort(403);
        }

        $user = auth()->user();
        $access = $this->assertAccess($user->id, $uuid);
        if (! $access['success']) {
            return response()->json(['success' => false, 'message' => $access['message']], 403);
        }

        $api = $this->teamService->apiForUser($user->id) ?? $this->coolify;
        $response = $api->applicationLogs($appUuid, (int) request('lines', 150));

        return response()->json($response);
    }

    /**
     * @return array{success: bool, message?: string}
     */
    protected function assertAccess(int $userId, string $projectUuid): array
    {
        $assigned = ClientCoolifyProject::query()
            ->where('user_id', $userId)
            ->where('project_uuid', $projectUuid)
            ->exists();

        if (! $assigned) {
            return ['success' => false, 'message' => 'المشروع غير مرتبط بحسابك'];
        }

        return $this->teamService->assertProjectInClientTeam($userId, $projectUuid);
    }
}
