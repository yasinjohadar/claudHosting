<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Jobs\WordpressSiteDatabaseBackupJob;
use App\Services\Coolify\DockerHostService;
use App\Services\Coolify\WordpressSiteAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ClientWordpressSiteDockerController extends Controller
{
    public function __construct(
        protected DockerHostService $dockerHost,
        protected WordpressSiteAccess $siteAccess
    ) {
        $this->middleware('auth');
    }

    public function stats(string $uuid): JsonResponse
    {
        $site = $this->siteAccess->resolveSite($uuid, auth()->user());

        return response()->json($this->dockerHost->getSiteContainerStats($site));
    }

    public function health(string $uuid): JsonResponse
    {
        $site = $this->siteAccess->resolveSite($uuid, auth()->user());

        return response()->json($this->dockerHost->getSiteHealth($site));
    }

    public function dbBackup(string $uuid): JsonResponse|RedirectResponse
    {
        if (! config('coolify.client_portal.actions.db_backup', true)) {
            abort(403);
        }

        $site = $this->siteAccess->resolveSite($uuid, auth()->user());
        $queue = config('coolify.defaults.wordpress_management_queue', 'default');
        WordpressSiteDatabaseBackupJob::dispatch($site)->onQueue($queue);

        $message = 'تم إرسال مهمة النسخ الاحتياطي لقاعدة البيانات';

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }
}
