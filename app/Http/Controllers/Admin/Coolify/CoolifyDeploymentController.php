<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;

class CoolifyDeploymentController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(protected CoolifyApiService $coolify)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $filterApp = $request->query('application_uuid');
        if ($filterApp) {
            $response = $this->coolify->listDeploymentsByApplication($filterApp);
        } else {
            $response = $this->coolify->listDeployments();
        }

        $deployments = $this->coolifyList($response);
        $statusFilter = $request->query('status');
        if ($statusFilter) {
            $deployments = array_values(array_filter(
                $deployments,
                fn (array $d) => str_contains(
                    strtolower((string) ($d['status'] ?? '')),
                    strtolower($statusFilter)
                )
            ));
        }

        $applications = $this->coolifyList($this->coolify->listApplications());
        $error = $response['success'] ? null : ($response['message'] ?? 'فشل جلب النشرات');

        return view('admin.coolify.deployments.index', compact(
            'deployments', 'applications', 'error', 'filterApp', 'statusFilter'
        ));
    }

    public function show(string $uuid)
    {
        $response = $this->coolify->getDeployment($uuid);
        $deployment = $this->coolifyItem($response);

        if (! $deployment) {
            return $this->coolifyRedirectError($response['message'] ?? 'غير موجود', 'admin.coolify.deployments.index');
        }

        return view('admin.coolify.deployments.show', compact('deployment', 'uuid'));
    }

    public function deploy(Request $request)
    {
        $validated = $request->validate([
            'uuid' => 'required|string',
            'force' => 'nullable|boolean',
        ]);

        $query = array_filter([
            'force' => ! empty($validated['force']) ? 'true' : null,
            'tag' => $request->input('tag'),
            'pr' => $request->input('pr'),
        ]);

        $response = $this->coolify->deploy($validated['uuid'], $query);
        $this->coolify->clearDashboardCache();

        if ($request->wantsJson()) {
            return response()->json($response);
        }

        if (! $response['success']) {
            return back()->with('error', $response['message'] ?? 'فشل النشر');
        }

        $this->logCoolify('deploy', 'application', $validated['uuid']);

        return back()->with('success', 'تم بدء النشر');
    }

    public function cancel(string $uuid)
    {
        $response = $this->coolify->cancelDeployment($uuid);
        $this->coolify->clearDashboardCache();

        if (! $response['success']) {
            return redirect()->route('admin.coolify.deployments.show', $uuid)
                ->with('error', $response['message'] ?? 'فشل الإلغاء');
        }

        $this->logCoolify('cancel', 'deployment', $uuid);

        return $this->coolifyRedirectSuccess('تم إلغاء النشر', 'admin.coolify.deployments.index');
    }
}
