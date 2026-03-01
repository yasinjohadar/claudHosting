<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupStorageConfig;
use App\Services\Backup\StorageAnalyticsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BackupStorageAnalyticsController extends Controller
{
    public function __construct(
        private StorageAnalyticsService $analyticsService
    ) {}

    /**
     * عرض صفحة التحليلات
     */
    public function index(Request $request)
    {
        $configs = BackupStorageConfig::where('is_active', true)->get();
        $selectedConfig = null;
        $stats = null;
        $budgetAlert = null;
        $period = $request->get('period', 'month'); // day, week, month, year

        if ($request->filled('config_id')) {
            $selectedConfig = BackupStorageConfig::findOrFail($request->config_id);
            
            $startDate = match($period) {
                'day' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                'year' => now()->startOfYear(),
                default => now()->startOfMonth(),
            };
            
            $endDate = now();
            $stats = $this->analyticsService->getStats($selectedConfig, $startDate, $endDate);
            
            // التحقق من الميزانية
            $budgetAlert = $this->analyticsService->checkBudgetAlert($selectedConfig);
        }

        return view('admin.pages.backup-storage.analytics', compact('configs', 'selectedConfig', 'stats', 'period', 'budgetAlert'));
    }
}

