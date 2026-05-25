<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\Coolify\CoolifyOperationsNotificationService;
use App\Services\Coolify\CoolifyOperationsService;
use App\Services\Coolify\CoolifyReadinessService;
use Illuminate\Http\Request;

class CoolifyOperationsController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(
        protected CoolifyOperationsService $operations,
        protected CoolifyReadinessService $readiness,
        protected CoolifyOperationsNotificationService $notifications
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $ops = $this->operations->build($request->boolean('refresh_ssh'));
        $readiness = $this->readiness->run();

        return view('admin.coolify.operations.index', compact('ops', 'readiness'));
    }

    public function checkAlerts()
    {
        $result = $this->notifications->checkAndNotify();

        if ($result['issues'] === []) {
            return back()->with('success', 'لا توجد مشاكل — لم تُرسل تنبيهات');
        }

        return back()->with(
            'success',
            'تم رصد '.count($result['issues']).' مشكلة — رسائل مرسلة: '.$result['sent']
        );
    }

    public function readiness(Request $request)
    {
        $result = $this->readiness->run($request->only(['server_uuid', 'project_uuid', 'ssh_host']));

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return view('admin.coolify.readiness.index', [
            'readiness' => $result,
            'overrides' => $request->only(['server_uuid', 'project_uuid', 'ssh_host']),
        ]);
    }
}
