<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupStorageConfig;
use App\Services\Backup\BackupStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BackupStorageController extends Controller
{
    public function __construct(
        private BackupStorageService $storageService
    ) {}

    /**
     * قائمة أماكن التخزين
     */
    public function index()
    {
        $configs = BackupStorageConfig::with('creator')
                                     ->orderBy('priority', 'desc')
                                     ->get();

        return view('admin.pages.backup-storage.index', compact('configs'));
    }

    /**
     * إضافة مكان تخزين
     */
    public function create()
    {
        $drivers = BackupStorageConfig::DRIVERS;
        $config = ['path' => 'backups']; // Default config
        return view('admin.pages.backup-storage.create', compact('drivers', 'config'));
    }

    /**
     * حفظ الإعدادات
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|in:' . implode(',', array_keys(BackupStorageConfig::DRIVERS)),
            'config' => 'nullable|array',
            'priority' => 'nullable|integer|min:0',
            'max_backups' => 'nullable|integer|min:1',
        ], [
            'name.required' => 'اسم الإعداد مطلوب',
            'name.string' => 'اسم الإعداد يجب أن يكون نصاً',
            'name.max' => 'اسم الإعداد لا يمكن أن يتجاوز 255 حرفاً',
            'driver.required' => 'نوع التخزين مطلوب',
            'driver.in' => 'نوع التخزين المحدد غير صالح',
            'config.array' => 'إعدادات التخزين يجب أن تكون مصفوفة',
            'priority.integer' => 'الأولوية يجب أن تكون رقماً',
            'priority.min' => 'الأولوية يجب أن تكون أكبر من أو تساوي 0',
            'max_backups.integer' => 'الحد الأقصى للنسخ يجب أن يكون رقماً',
            'max_backups.min' => 'الحد الأقصى للنسخ يجب أن يكون أكبر من 0',
        ]);

        try {
            $configData = $request->input('config', []);

            // التحقق من وجود البيانات المطلوبة حسب نوع التخزين
            $driver = $validated['driver'];
            $this->validateDriverConfig($driver, $configData);

            $pricingConfig = [];
            if ($request->filled('pricing_config.storage_cost_per_gb') || 
                $request->filled('pricing_config.upload_cost_per_gb') || 
                $request->filled('pricing_config.download_cost_per_gb')) {
                $pricingConfig = [
                    'storage_cost_per_gb' => $request->input('pricing_config.storage_cost_per_gb'),
                    'upload_cost_per_gb' => $request->input('pricing_config.upload_cost_per_gb'),
                    'download_cost_per_gb' => $request->input('pricing_config.download_cost_per_gb'),
                ];
            }

            BackupStorageConfig::create([
                'name' => $validated['name'],
                'driver' => $validated['driver'],
                'config' => $configData,
                'priority' => $validated['priority'] ?? 0,
                'max_backups' => $validated['max_backups'] ?? null,
                'created_by' => Auth::id(),
                'is_active' => $request->has('is_active'),
                'redundancy' => $request->has('redundancy'),
                'pricing_config' => !empty($pricingConfig) ? $pricingConfig : null,
                'monthly_budget' => $request->filled('monthly_budget') ? $request->input('monthly_budget') : null,
                'cost_alert_threshold' => $request->filled('cost_alert_threshold') ? $request->input('cost_alert_threshold') : null,
            ]);

            return redirect()->route('admin.backup-storage.index')
                           ->with('success', 'تم إضافة مكان التخزين بنجاح.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error creating backup storage config: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['config']),
            ]);
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء إضافة مكان التخزين: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * التحقق من صحة إعدادات نوع التخزين
     */
    private function validateDriverConfig(string $driver, array $configData): void
    {
        $requiredFields = [];
        $fieldLabels = [
            'access_key_id' => 'Access Key ID',
            'secret_access_key' => 'Secret Access Key',
            'bucket' => 'Bucket',
            'region' => 'Region',
            'account_id' => 'Account ID',
            'host' => 'Host',
            'username' => 'Username',
            'password' => 'Password',
            'account_name' => 'Account Name',
            'account_key' => 'Account Key',
            'container' => 'Container',
        ];

        switch ($driver) {
            case 'local':
                // لا توجد حقول مطلوبة
                break;
            case 's3':
                $requiredFields = ['access_key_id', 'secret_access_key', 'bucket', 'region'];
                break;
            case 'digitalocean':
            case 'wasabi':
            case 'backblaze':
                $requiredFields = ['access_key_id', 'secret_access_key', 'bucket'];
                break;
            case 'cloudflare_r2':
                $requiredFields = ['account_id', 'access_key_id', 'secret_access_key', 'bucket'];
                break;
            case 'ftp':
            case 'sftp':
                $requiredFields = ['host', 'username', 'password'];
                break;
            case 'azure':
                $requiredFields = ['account_name', 'account_key', 'container'];
                break;
        }

        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (empty($configData[$field])) {
                $missingFields[] = $fieldLabels[$field] ?? $field;
            }
        }

        if (!empty($missingFields)) {
            throw new \Exception('الحقول التالية مطلوبة لإعدادات ' . (BackupStorageConfig::DRIVERS[$driver] ?? $driver) . ': ' . implode(', ', $missingFields));
        }
    }

    /**
     * تعديل الإعدادات
     */
    public function edit(BackupStorageConfig $config)
    {
        $drivers = BackupStorageConfig::DRIVERS;
        $config->load('creator');
        return view('admin.pages.backup-storage.edit', compact('config', 'drivers'));
    }

    /**
     * تحديث الإعدادات
     */
    public function update(Request $request, BackupStorageConfig $config)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|in:' . implode(',', array_keys(BackupStorageConfig::DRIVERS)),
            'config' => 'required|array',
            'priority' => 'nullable|integer|min:0',
            'max_backups' => 'nullable|integer|min:1',
        ]);

        try {
            // دمج config مع القيم القديمة (للحفاظ على passwords)
            $configData = $request->input('config', []);
            $oldConfig = $config->getDecryptedConfig();
            
            foreach ($configData as $key => $value) {
                // إذا كان الحقل فارغاً وكان password/token، احتفظ بالقيمة القديمة
                if (empty($value) && (str_contains($key, 'password') || str_contains($key, 'token') || str_contains($key, 'secret') || str_contains($key, 'key'))) {
                    if (isset($oldConfig[$key])) {
                        $configData[$key] = $oldConfig[$key];
                    }
                }
            }

            $config->update([
                'name' => $validated['name'],
                'driver' => $validated['driver'],
                'config' => $configData,
                'priority' => $validated['priority'] ?? 0,
                'max_backups' => $validated['max_backups'] ?? null,
                'is_active' => $request->has('is_active'),
                'redundancy' => $request->has('redundancy'),
                'pricing_config' => [
                    'storage_cost_per_gb' => $request->input('pricing_config.storage_cost_per_gb'),
                    'upload_cost_per_gb' => $request->input('pricing_config.upload_cost_per_gb'),
                    'download_cost_per_gb' => $request->input('pricing_config.download_cost_per_gb'),
                ],
                'monthly_budget' => $request->input('monthly_budget'),
                'cost_alert_threshold' => $request->input('cost_alert_threshold'),
            ]);

            return redirect()->route('admin.backup-storage.index')
                           ->with('success', 'تم تحديث إعدادات التخزين بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error updating backup storage config: ' . $e->getMessage());
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء تحديث الإعدادات: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * حذف الإعدادات
     */
    public function destroy(BackupStorageConfig $config)
    {
        try {
            $config->delete();

            return redirect()->route('admin.backup-storage.index')
                           ->with('success', 'تم حذف إعدادات التخزين بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error deleting backup storage config: ' . $e->getMessage());
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء حذف الإعدادات: ' . $e->getMessage());
        }
    }

    /**
     * اختبار الاتصال (للنسخ المحفوظة)
     */
    public function test(BackupStorageConfig $config)
    {
        try {
            $result = $config->testConnection();

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في الاختبار: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * اختبار الاتصال (من الـ form قبل الحفظ)
     */
    public function testConnection(Request $request)
    {
        try {
            $validated = $request->validate([
                'driver' => 'required|in:' . implode(',', array_keys(BackupStorageConfig::DRIVERS)),
                'config' => 'required|array',
            ]);

            $driver = $validated['driver'];
            $configData = $request->input('config', []);

            // التحقق من الحقول المطلوبة
            $this->validateDriverConfig($driver, $configData);

            // إنشاء config مؤقت للاختبار
            // يجب أن نستخدم setConfigAttribute لتشفير config بشكل صحيح
            $tempConfig = new BackupStorageConfig();
            $tempConfig->driver = $driver;
            $tempConfig->config = $configData; // سيتم تشفيره تلقائياً بواسطة setConfigAttribute

            // اختبار الاتصال
            $result = $tempConfig->testConnection();

            return response()->json($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $errorMessages = [];
            foreach ($errors as $field => $messages) {
                $errorMessages = array_merge($errorMessages, $messages);
            }
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات: ' . implode(', ', $errorMessages),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Storage connection test failed: ' . $e->getMessage(), [
                'driver' => $request->input('driver'),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'خطأ في الاختبار: ' . $e->getMessage(),
            ], 500);
        }
    }
}
