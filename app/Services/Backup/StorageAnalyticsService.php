<?php

namespace App\Services\Backup;

use App\Models\BackupStorageConfig;
use App\Models\AppStorageConfig;
use App\Models\StorageAnalytic;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StorageAnalyticsService
{
    /**
     * تتبع استخدام التخزين
     * يدعم كلا من BackupStorageConfig و AppStorageConfig
     */
    public function trackStorageUsage(BackupStorageConfig|AppStorageConfig $config, int $bytes): void
    {
        try {
            $date = now()->toDateString();
            
            StorageAnalytic::updateOrCreate(
                [
                    'storage_config_id' => $config->id,
                    'date' => $date,
                ],
                [
                    'bytes_stored' => \DB::raw("bytes_stored + {$bytes}"),
                    'cost' => \DB::raw("cost + " . $this->calculateCost($config, $bytes)),
                ]
            );

            // تحديث bytes_stored بشكل صحيح
            $analytic = StorageAnalytic::where('storage_config_id', $config->id)
                ->where('date', $date)
                ->first();
            
            if ($analytic) {
                $analytic->increment('bytes_stored', $bytes);
                $analytic->increment('cost', $this->calculateCost($config, $bytes));
            }
        } catch (\Exception $e) {
            Log::error('Error tracking storage usage: ' . $e->getMessage());
        }
    }

    /**
     * تتبع النطاق الترددي
     * يدعم كلا من BackupStorageConfig و AppStorageConfig
     */
    public function trackBandwidth(BackupStorageConfig|AppStorageConfig $config, string $operation, int $bytes): void
    {
        try {
            $date = now()->toDateString();
            
            $analytic = StorageAnalytic::firstOrCreate(
                [
                    'storage_config_id' => $config->id,
                    'date' => $date,
                ],
                [
                    'bytes_stored' => 0,
                    'bytes_uploaded' => 0,
                    'bytes_downloaded' => 0,
                    'cost' => 0,
                    'operations_count' => 0,
                ]
            );

            if ($operation === 'upload') {
                $analytic->increment('bytes_uploaded', $bytes);
                $analytic->increment('operations_count');
                
                // حساب تكلفة الرفع
                $uploadCost = $this->calculateUploadCost($config, $bytes);
                $analytic->increment('cost', $uploadCost);
            } elseif ($operation === 'download') {
                $analytic->increment('bytes_downloaded', $bytes);
                $analytic->increment('operations_count');
                
                // حساب تكلفة التحميل
                $downloadCost = $this->calculateDownloadCost($config, $bytes);
                $analytic->increment('cost', $downloadCost);
            }
        } catch (\Exception $e) {
            Log::error('Error tracking bandwidth: ' . $e->getMessage());
        }
    }

    /**
     * حساب تكلفة التخزين
     */
    private function calculateCost(BackupStorageConfig|AppStorageConfig $config, int $bytes): float
    {
        $pricing = $config->pricing_config ?? [];
        $gb = $bytes / (1024 ** 3);
        $costPerGb = $pricing['storage_cost_per_gb'] ?? 0;
        
        return round($gb * $costPerGb, 4);
    }

    /**
     * حساب تكلفة الرفع
     */
    private function calculateUploadCost(BackupStorageConfig|AppStorageConfig $config, int $bytes): float
    {
        $pricing = $config->pricing_config ?? [];
        $gb = $bytes / (1024 ** 3);
        $costPerGb = $pricing['upload_cost_per_gb'] ?? 0;
        
        return round($gb * $costPerGb, 4);
    }

    /**
     * حساب تكلفة التحميل
     */
    private function calculateDownloadCost(BackupStorageConfig|AppStorageConfig $config, int $bytes): float
    {
        $pricing = $config->pricing_config ?? [];
        $gb = $bytes / (1024 ** 3);
        $costPerGb = $pricing['download_cost_per_gb'] ?? 0;
        
        return round($gb * $costPerGb, 4);
    }

    /**
     * الحصول على إحصائيات لفترة معينة
     */
    public function getStats(BackupStorageConfig|AppStorageConfig $config, Carbon $startDate, Carbon $endDate): array
    {
        $analytics = StorageAnalytic::where('storage_config_id', $config->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return [
            'total_bytes_stored' => $analytics->sum('bytes_stored'),
            'total_bytes_uploaded' => $analytics->sum('bytes_uploaded'),
            'total_bytes_downloaded' => $analytics->sum('bytes_downloaded'),
            'total_cost' => $analytics->sum('cost'),
            'total_operations' => $analytics->sum('operations_count'),
            'daily_average_cost' => $analytics->avg('cost'),
            'daily_average_storage' => $analytics->avg('bytes_stored'),
        ];
    }

    /**
     * الحصول على إحصائيات الشهر الحالي
     */
    public function getMonthlyStats(BackupStorageConfig|AppStorageConfig $config): array
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        
        return $this->getStats($config, $startDate, $endDate);
    }

    /**
     * التحقق من تجاوز الميزانية
     */
    public function checkBudgetAlert(BackupStorageConfig|AppStorageConfig $config): ?array
    {
        if (!$config->monthly_budget) {
            return null;
        }

        $monthlyStats = $this->getMonthlyStats($config);
        $currentCost = $monthlyStats['total_cost'];
        
        if ($currentCost >= $config->monthly_budget) {
            return [
                'alert' => true,
                'message' => "تم تجاوز الميزانية الشهرية ({$config->monthly_budget})",
                'current_cost' => $currentCost,
                'budget' => $config->monthly_budget,
            ];
        }

        if ($config->cost_alert_threshold && $currentCost >= $config->cost_alert_threshold) {
            return [
                'alert' => true,
                'message' => "تم الوصول إلى حد التنبيه ({$config->cost_alert_threshold})",
                'current_cost' => $currentCost,
                'threshold' => $config->cost_alert_threshold,
            ];
        }

        return null;
    }
}

