<?php

namespace App\Http\Controllers\Admin\Infrastructure;

use App\Http\Controllers\Controller;
use App\Models\VpsServer;
use App\Services\Infrastructure\VpsMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InfrastructureMetricsController extends Controller
{
    public function __construct(protected VpsMetricsService $metrics)
    {
        $this->middleware('auth');
        $this->middleware('throttle:30,1')->only(['live']);
    }

    public function live(Request $request, string $uuid): JsonResponse
    {
        $server = VpsServer::query()->where('uuid', $uuid)->firstOrFail();
        $data = $this->metrics->getLiveMetrics($server, $request->boolean('refresh'));

        return response()->json(array_merge($data, [
            'refresh_seconds' => $this->metrics->refreshSeconds(),
        ]));
    }

    public function history(Request $request, string $uuid): JsonResponse
    {
        $server = VpsServer::query()->where('uuid', $uuid)->firstOrFail();
        $range = $request->string('range', '24h')->toString();
        $data = $this->metrics->getHistory($server, $range);

        return response()->json($data);
    }
}
