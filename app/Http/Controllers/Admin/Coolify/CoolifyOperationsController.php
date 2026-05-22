<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\Coolify\CoolifyOperationsService;
use App\Services\Coolify\CoolifyReadinessService;
use Illuminate\Http\Request;

class CoolifyOperationsController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(
        protected CoolifyOperationsService $operations,
        protected CoolifyReadinessService $readiness
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $ops = $this->operations->build($request->boolean('refresh_ssh'));
        $readiness = $this->readiness->run();

        return view('admin.coolify.operations.index', compact('ops', 'readiness'));
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
