<?php

namespace App\Http\Controllers\Admin\Infrastructure;

use App\Http\Controllers\Controller;
use App\Models\VpsServer;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Infrastructure\VpsTerminalSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InfrastructureTerminalController extends Controller
{
    public function __construct(
        protected VpsTerminalSessionService $terminal,
        protected CoolifySettingsService $settings
    ) {
        $this->middleware('auth');
    }

    public function show(string $uuid): View
    {
        $server = VpsServer::query()->where('uuid', $uuid)->firstOrFail();
        $readiness = $this->terminal->readiness($server);
        $bridge = $this->settings->getTerminalBridgeConfig();

        return view('admin.infrastructure.servers.terminal', [
            'server' => $server,
            'readiness' => $readiness,
            'bridgeEnabled' => (bool) ($bridge['enabled'] ?? false),
        ]);
    }

    public function session(string $uuid): JsonResponse
    {
        $server = VpsServer::query()->where('uuid', $uuid)->firstOrFail();
        $result = $this->terminal->createSession($server, (int) Auth::id());

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function commands(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'groups' => config('vps_terminal_commands.groups', []),
            'bridge_enabled' => (bool) ($this->settings->getTerminalBridgeConfig()['enabled'] ?? false),
        ]);
    }
}
