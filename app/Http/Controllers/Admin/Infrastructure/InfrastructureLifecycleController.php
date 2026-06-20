<?php

namespace App\Http\Controllers\Admin\Infrastructure;

use App\Http\Controllers\Controller;
use App\Models\VpsServer;
use App\Services\Infrastructure\VpsLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InfrastructureLifecycleController extends Controller
{
    public function __construct(protected VpsLifecycleService $lifecycle)
    {
        $this->middleware('auth');
    }

    public function images(string $uuid): JsonResponse
    {
        $server = VpsServer::query()->where('uuid', $uuid)->firstOrFail();
        $data = $this->lifecycle->listImages($server);

        return response()->json($data);
    }

    public function reinstall(Request $request, string $uuid): RedirectResponse
    {
        $server = VpsServer::query()->where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'image_id' => 'required|string|max:255',
            'confirm_reinstall' => 'accepted',
        ]);

        $result = $this->lifecycle->reinstall($server, $validated['image_id']);

        return back()->with(
            ($result['success'] ?? false) ? 'success' : 'error',
            $result['message'] ?? '—'
        );
    }
}
