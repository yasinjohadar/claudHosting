<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Controller;
use App\Services\Coolify\CoolifyMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoolifyMetricsController extends Controller
{
    public function __construct(protected CoolifyMetricsService $metrics)
    {
        $this->middleware('auth');
    }

    public function overview(Request $request): JsonResponse
    {
        $data = $this->metrics->getOverviewMetrics($request->boolean('refresh'));

        return response()->json(array_merge($data, [
            'refresh_seconds' => $this->metrics->refreshSeconds(),
        ]));
    }

    public function server(Request $request, string $uuid): JsonResponse
    {
        $data = $this->metrics->getServerMetrics($uuid, $request->boolean('refresh'));

        return response()->json(array_merge($data, [
            'refresh_seconds' => $this->metrics->refreshSeconds(),
        ]));
    }

    public function project(Request $request, string $uuid): JsonResponse
    {
        $data = $this->metrics->getProjectMetrics($uuid, $request->boolean('refresh'));

        return response()->json(array_merge($data, [
            'refresh_seconds' => $this->metrics->refreshSeconds(),
        ]));
    }

    public function resource(Request $request, string $type, string $uuid): JsonResponse
    {
        if (! in_array($type, ['application', 'service', 'database'], true)) {
            return response()->json(['success' => false, 'message' => 'نوع مورد غير مدعوم'], 400);
        }

        $data = $this->metrics->getResourceMetrics($type, $uuid, $request->boolean('refresh'));

        return response()->json(array_merge($data, [
            'refresh_seconds' => $this->metrics->refreshSeconds(),
        ]));
    }
}
