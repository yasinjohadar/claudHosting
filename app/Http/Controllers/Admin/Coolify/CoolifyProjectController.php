<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Admin\Coolify\Concerns\LogsCoolifyActivity;
use App\Http\Controllers\Controller;
use App\Jobs\RestoreProjectSnapshotJob;
use App\Models\CoolifyProjectSnapshot;
use App\Models\ClientCoolifyProject;
use App\Models\User;
use App\Services\Client\ClientAssetService;
use App\Services\Coolify\CoolifyProjectCleanupService;
use App\Services\Coolify\CoolifyTeamService;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;

class CoolifyProjectController extends Controller
{
    use HandlesCoolifyResponses;
    use LogsCoolifyActivity;

    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifyProjectCleanupService $projectCleanup,
        protected ClientAssetService $clientAssets,
        protected CoolifyTeamService $teamService
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $response = $this->coolify->listProjects();
        $projects = $this->coolifyList($response);
        $error = $response['success'] ? null : ($response['message'] ?? 'فشل جلب المشاريع');

        $assignments = $this->clientAssets->coolifyProjectAssignmentMap();
        $filterUserId = $request->filled('user_id') ? (int) $request->user_id : null;

        foreach ($projects as $index => $project) {
            $uuid = trim((string) ($project['uuid'] ?? ''));
            $assignment = $assignments[$uuid] ?? null;
            $projects[$index]['_client'] = $assignment?->client;
            $projects[$index]['_user_id'] = $assignment?->user_id;

            if ($filterUserId !== null && (int) ($assignment?->user_id ?? 0) !== $filterUserId) {
                unset($projects[$index]);
                continue;
            }

            $projects[$index]['_inspection'] = $uuid !== ''
                ? $this->projectCleanup->inspectProject($uuid)
                : [
                    'total' => 0,
                    'can_delete' => false,
                    'summary_label' => '—',
                    'fetch_error' => 'بدون UUID',
                ];
        }

        $projects = array_values($projects);
        $clientUsers = User::query()->orderBy('name')->select(['id', 'name', 'email'])->limit(500)->get();

        return view('admin.coolify.projects.index', compact('projects', 'error', 'clientUsers', 'filterUserId'));
    }

    public function assignClient(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'project_name' => 'nullable|string|max:255',
        ]);

        $userId = isset($validated['user_id']) && $validated['user_id'] !== ''
            ? (int) $validated['user_id']
            : null;

        $projectName = $validated['project_name'] ?? null;
        if ($projectName === null || $projectName === '') {
            $existing = $this->coolify->getProject($uuid);
            if ($existing['success'] ?? false) {
                $item = $this->coolifyItem($existing);
                $projectName = is_array($item) ? ($item['name'] ?? null) : null;
            }
        }

        $result = $this->clientAssets->assignCoolifyProject($userId, $uuid, $projectName);

        if ($request->wantsJson() || $request->ajax()) {
            $client = $result['project']?->client;

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'html' => view('admin.coolify.projects.partials.client-cell', [
                    'uuid' => $uuid,
                    'client' => $client,
                ])->render(),
            ], $result['success'] ? 200 : 422);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function create()
    {
        $clientUsers = User::query()
            ->whereHas('clientCoolifyTeam', function ($q) {
                $q->whereNotNull('api_token');
            })
            ->orderBy('name')
            ->select(['id', 'name', 'email'])
            ->get();

        return view('admin.coolify.projects.create', compact('clientUsers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $api = $this->coolify;
        $userId = isset($validated['user_id']) ? (int) $validated['user_id'] : null;

        if ($userId !== null) {
            $clientApi = $this->teamService->apiForUser($userId);
            if ($clientApi === null) {
                return back()->withInput()->with(
                    'error',
                    'اربط فريق Coolify وتوكنه للعميل أولاً من قسم فرق العمل'
                );
            }
            $api = $clientApi;
        }

        $response = $api->createProject([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);
        $this->coolify->clearDashboardCache();

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل إنشاء المشروع');
        }

        $item = $this->coolifyItem($response);
        $uuid = $item ? $this->resourceUuid($item) : null;

        if ($uuid) {
            $this->logCoolify('create', 'project', $uuid, $validated['name'] ?? null);

            if ($userId !== null) {
                $this->clientAssets->assignCoolifyProject($userId, $uuid, $validated['name'] ?? null);
            }

            return $this->coolifyRedirectSuccess('تم إنشاء المشروع', 'admin.coolify.projects.show', ['uuid' => $uuid]);
        }

        $this->logCoolify('create', 'project', null, $validated['name'] ?? null);

        return $this->coolifyRedirectSuccess('تم إنشاء المشروع', 'admin.coolify.projects.index');
    }

    public function show(string $uuid)
    {
        $response = $this->coolify->getProject($uuid);
        $project = $this->coolifyItem($response);

        if (! $project) {
            return $this->coolifyRedirectError($response['message'] ?? 'المشروع غير موجود', 'admin.coolify.projects.index');
        }

        $resourcesResponse = $this->coolify->projectResources($uuid);
        $resources = $this->coolifyList($resourcesResponse);
        $inspection = $this->projectCleanup->inspectProject($uuid);
        $projectSnapshots = CoolifyProjectSnapshot::query()
            ->where('project_uuid', $uuid)
            ->with('items')
            ->latest()
            ->limit(8)
            ->get();

        $assignment = ClientCoolifyProject::where('project_uuid', $uuid)->first();
        $clientUsers = User::query()->orderBy('name')->select(['id', 'name', 'email'])->limit(500)->get();

        return view('admin.coolify.projects.show', compact(
            'project', 'uuid', 'resources', 'inspection', 'projectSnapshots', 'assignment', 'clientUsers'
        ));
    }

    public function restoreSnapshot(Request $request, string $uuid, string $snapshotUuid)
    {
        $snapshot = CoolifyProjectSnapshot::where('uuid', $snapshotUuid)
            ->where('project_uuid', $uuid)
            ->with('items')
            ->first();

        if (! $snapshot) {
            return $this->coolifyRedirectError('اللقطة غير موجودة', 'admin.coolify.projects.show', ['uuid' => $uuid]);
        }

        $validated = $request->validate([
            'restore_scope' => 'required|in:all,project,selected',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'integer',
            'stop_before_restore' => 'nullable|boolean',
            'redeploy' => 'nullable|boolean',
        ]);

        $itemIds = null;
        $options = [
            'stop_before_restore' => $request->boolean('stop_before_restore', true),
            'redeploy' => $request->boolean('redeploy'),
        ];

        if ($validated['restore_scope'] === 'project') {
            $options['scope'] = 'project';
        } elseif ($validated['restore_scope'] === 'selected') {
            $itemIds = $validated['item_ids'] ?? [];
        }

        RestoreProjectSnapshotJob::dispatch($snapshot->id, $itemIds, $options);
        $this->logCoolify('snapshot_restore', 'project', $uuid, $snapshot->name);

        return $this->coolifyRedirectSuccess(
            'بدأت عملية الاستعادة',
            'admin.coolify.projects.show',
            ['uuid' => $uuid]
        );
    }

    public function edit(string $uuid)
    {
        $response = $this->coolify->getProject($uuid);
        $project = $this->coolifyItem($response);

        if (! $project) {
            return $this->coolifyRedirectError($response['message'] ?? 'المشروع غير موجود', 'admin.coolify.projects.index');
        }

        return view('admin.coolify.projects.edit', compact('project', 'uuid'));
    }

    public function update(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $response = $this->coolify->updateProject($uuid, $validated);
        $this->coolify->clearDashboardCache();

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل التحديث');
        }

        return $this->coolifyRedirectSuccess('تم تحديث المشروع', 'admin.coolify.projects.show', ['uuid' => $uuid]);
    }

    public function destroy(string $uuid)
    {
        $result = $this->projectCleanup->purgeForProject($uuid);
        $this->coolify->clearDashboardCache();

        if (! ($result['success'] ?? false)) {
            return redirect()
                ->back()
                ->with('error', $result['message'] ?? 'فشل الحذف');
        }

        $this->logCoolify('delete', 'project', $uuid);

        $successMessage = $result['message'] ?? 'تم حذف المشروع';
        if (! empty($result['warnings'])) {
            $successMessage .= ' — تحذيرات: '.implode('؛ ', array_slice($result['warnings'], 0, 3));
        }

        return redirect()
            ->route('admin.coolify.projects.index')
            ->with('success', $successMessage);
    }

    public function resources(string $uuid)
    {
        $response = $this->coolify->projectResources($uuid);
        $resources = $this->coolifyList($response);
        $returnUrl = route('admin.coolify.projects.show', $uuid);

        return view('admin.coolify.projects.resources', compact('uuid', 'resources', 'response', 'returnUrl'));
    }

    public function environment(string $uuid, string $environment)
    {
        $response = $this->coolify->projectEnvironment($uuid, $environment);
        $data = $this->coolifyItem($response) ?? $response['data'] ?? null;

        return view('admin.coolify.projects.environment', [
            'uuid' => $uuid,
            'environment' => $environment,
            'data' => $data,
            'response' => $response,
        ]);
    }
}
