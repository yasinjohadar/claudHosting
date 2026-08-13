<?php

namespace App\Http\Controllers\Concerns;

use App\Models\WhmAccount;
use App\Models\WhmWordpressOperation;
use App\Models\WhmWordpressSite;
use App\Services\Whm\Wordpress\WhmWordpressManagementService;
use App\Support\WhmWordpressSiteRouteMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ManagesWhmWordpressSiteActions
{
    protected function whmWordpressManagement(): WhmWordpressManagementService
    {
        return app(WhmWordpressManagementService::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildWhmWordpressShowData(WhmAccount $account, WhmWordpressSite $site, string $panel): array
    {
        $site->loadMissing('account');
        $management = $this->whmWordpressManagement();
        $wpManagementState = $management->getManagementState($site);
        $wpCanManage = $management->canManage($site);
        $wpInfo = ($site->metadata ?? [])['wp_info'] ?? null;
        $wpQueueStuck = WhmWordpressOperation::query()
            ->where('whm_wordpress_site_id', $site->id)
            ->where('status', 'queued')
            ->where('created_at', '<', now()->subSeconds(60))
            ->exists();

        return [
            'account' => $account,
            'site' => $site,
            'uuid' => (string) $site->id,
            'wpCanManage' => $wpCanManage,
            'wpManagementState' => $wpManagementState,
            'wpInfo' => $wpInfo,
            'wpQueueStuck' => $wpQueueStuck,
            'wpManagementQueueName' => (string) config('whm.wordpress_management_queue', 'default'),
            'wpPanel' => $panel,
            'isClientPanel' => $panel === 'client',
            'hideDockerTab' => true,
            'wpSettingsUrl' => $panel === 'admin'
                ? route('admin.whm.settings.index')
                : route('client.services'),
            'wpSiteRoutes' => WhmWordpressSiteRouteMap::forPanel($panel, $account->id, $site->id),
            'embeddedInSiteShow' => true,
        ];
    }

    public function status(WhmAccount $account, WhmWordpressSite $site): JsonResponse
    {
        $this->guardWhmWordpressSite($account, $site);
        $state = $this->whmWordpressManagement()->getManagementState($site);

        return response()->json([
            'success' => true,
            'status' => $site->status,
            'wp_management' => $state,
        ]);
    }

    public function wpInfo(WhmAccount $account, WhmWordpressSite $site, Request $request): JsonResponse
    {
        $this->guardWhmWordpressSite($account, $site);
        $management = $this->whmWordpressManagement();

        if ($request->boolean('refresh')) {
            $management->clearStuckWpJob($site);
            $queued = $management->executeAction($site, 'refresh_info', [], Auth::id());
            if ($queued['async'] ?? false) {
                return response()->json([
                    'success' => true,
                    'async' => true,
                    'job_id' => $queued['job_id'] ?? null,
                    'message' => $queued['message'] ?? 'جاري التحديث',
                ]);
            }

            return response()->json($queued);
        }

        return response()->json($management->getSiteInfo($site, false));
    }

    public function wpAction(WhmAccount $account, WhmWordpressSite $site, Request $request): JsonResponse
    {
        $this->guardWhmWordpressSite($account, $site);

        $action = (string) $request->input('action', '');
        $params = $request->except(['action', '_token']);

        $result = $this->whmWordpressManagement()->executeAction($site, $action, $params, Auth::id());

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function wpJob(WhmAccount $account, WhmWordpressSite $site): JsonResponse
    {
        $this->guardWhmWordpressSite($account, $site);

        return response()->json($this->whmWordpressManagement()->getJobStatus($site->fresh()));
    }

    public function wpOperations(WhmAccount $account, WhmWordpressSite $site): JsonResponse
    {
        $this->guardWhmWordpressSite($account, $site);

        $query = WhmWordpressOperation::query()
            ->where('whm_wordpress_site_id', $site->id)
            ->with('user:id,name')
            ->orderByDesc('id');

        $beforeId = (int) request()->query('before_id', 0);
        if ($beforeId > 0) {
            $query->where('id', '<', $beforeId);
        }

        $operations = $query->limit(25)->get();

        return response()->json([
            'success' => true,
            'operations' => $operations->map(fn (WhmWordpressOperation $op) => [
                'id' => $op->id,
                'action' => $op->action,
                'action_label' => $op->action_label ?? $op->action,
                'status' => $op->status,
                'success' => $op->success,
                'message' => $op->message,
                'output' => $op->output,
                'user_name' => $op->user?->name,
                'has_file' => $op->hasDownloadableFile(),
                'result_file_size' => $op->result_file_size,
                'started_at' => $op->started_at?->toIso8601String(),
                'finished_at' => $op->finished_at?->toIso8601String(),
            ]),
            'has_more' => $operations->count() === 25,
        ]);
    }

    public function downloadWpOperationFile(WhmAccount $account, WhmWordpressSite $site, WhmWordpressOperation $operation): StreamedResponse
    {
        $this->guardWhmWordpressSite($account, $site);

        abort_unless((int) $operation->whm_wordpress_site_id === (int) $site->id, 404);

        if (! $operation->hasDownloadableFile() || ! Storage::disk('local')->exists($operation->result_file_path)) {
            abort(404, 'الملف غير متوفر');
        }

        $downloadName = $operation->action.'-'.$operation->id.'.sql.gz';

        return Storage::disk('local')->download($operation->result_file_path, $downloadName);
    }

    abstract protected function guardWhmWordpressSite(WhmAccount $account, WhmWordpressSite $site): void;
}
