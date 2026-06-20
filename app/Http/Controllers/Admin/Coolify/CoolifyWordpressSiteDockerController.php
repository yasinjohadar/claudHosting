<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Concerns\ResolvesAuthorizedWordpressSite;
use App\Http\Controllers\Controller;
use App\Jobs\WordpressSiteDatabaseBackupJob;
use App\Services\Coolify\DockerHostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CoolifyWordpressSiteDockerController extends Controller
{
    use HandlesCoolifyResponses;
    use ResolvesAuthorizedWordpressSite;

    public function __construct(protected DockerHostService $dockerHost)
    {
        $this->middleware('auth');
    }

    public function stats(string $uuid): JsonResponse
    {
        $site = $this->resolveAuthorizedWordpressSite($uuid);

        return response()->json($this->dockerHost->getSiteContainerStats($site));
    }

    public function health(string $uuid): JsonResponse
    {
        $site = $this->resolveAuthorizedWordpressSite($uuid);

        return response()->json($this->dockerHost->getSiteHealth($site));
    }

    public function dbBackup(string $uuid): JsonResponse|RedirectResponse
    {
        $site = $this->resolveAuthorizedWordpressSite($uuid);
        $queue = config('coolify.defaults.wordpress_management_queue', 'default');
        WordpressSiteDatabaseBackupJob::dispatch($site)->onQueue($queue);

        $message = 'تم إرسال مهمة النسخ الاحتياطي لقاعدة البيانات';

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function dbRestore(string $uuid): JsonResponse|RedirectResponse
    {
        $site = $this->resolveAuthorizedWordpressSite($uuid);

        $validated = request()->validate([
            'backup_path' => 'required|string|max:500',
        ]);

        $path = $validated['backup_path'];
        if (! str_starts_with($path, 'wordpress-db-backups/'.$site->uuid.'/')) {
            return back()->with('error', 'مسار النسخة غير صالح لهذا الموقع.');
        }

        $result = $this->dockerHost->restoreDatabaseBackup($site, $path);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
        }

        return ($result['success'] ?? false)
            ? back()->with('success', $result['message'] ?? 'تمت الاستعادة')
            : back()->with('error', $result['message'] ?? 'فشلت الاستعادة');
    }
}
