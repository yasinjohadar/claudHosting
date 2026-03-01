<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppStorageConfig;
use App\Services\Storage\AppStorageAnalyticsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppStorageAnalyticsController extends Controller
{
    public function __construct(
        private AppStorageAnalyticsService $analyticsService
    ) {}

    /**
     * عرض صفحة التحليلات
     */
    public function index(Request $request)
    {
        $configs = AppStorageConfig::where('is_active', true)->get();
        $selectedConfig = null;
        $stats = null;
        $period = $request->get('period', 'month');
        $fileType = $request->get('file_type');

        if ($request->filled('config_id')) {
            $selectedConfig = AppStorageConfig::findOrFail($request->config_id);
            
            $startDate = match($period) {
                'day' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                'year' => now()->startOfYear(),
                default => now()->startOfMonth(),
            };
            
            $endDate = now();
            $stats = $this->analyticsService->getStats($selectedConfig, $startDate, $endDate, $fileType);
            
            $budgetAlert = $this->analyticsService->checkBudgetAlert($selectedConfig);
        }

        $budgetAlert = $budgetAlert ?? null;
        return view('admin.pages.app-storage.analytics', compact('configs', 'selectedConfig', 'stats', 'period', 'fileType', 'budgetAlert'));
    }
}
