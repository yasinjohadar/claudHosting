<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppStorageConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AppStorageController extends Controller
{
    /**
     * قائمة أماكن التخزين
     */
    public function index()
    {
        $configs = AppStorageConfig::with('creator')
                                   ->orderBy('priority', 'desc')
                                   ->get();

        return view('admin.pages.app-storage.index', compact('configs'));
    }

    /**
     * إضافة مكان تخزين
     */
    public function create()
    {
        try {
            $drivers = AppStorageConfig::DRIVERS;
            return view('admin.pages.app-storage.create', compact('drivers'));
        } catch (\Exception $e) {
            Log::error('Error in AppStorageController::create: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('admin.storage.index')
                ->with('error', 'حدث خطأ أثناء تحميل الصفحة: ' . $e->getMessage());
        }
    }

    /**
     * حفظ الإعدادات
     */
    public function store(Request $request)
    {
        // التحقق الأساسي من الحقول العامة
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|in:' . implode(',', array_keys(AppStorageConfig::DRIVERS)),
            'config' => 'nullable|array', // لم تعد مطلوبة دائماً
            'priority' => 'nullable|integer|min:0',
            'cdn_url' => 'nullable|url',
            'file_types' => 'nullable|array',
        ], [
            'name.required' => 'اسم الإعداد مطلوب',
            'name.string' => 'اسم الإعداد يجب أن يكون نصاً',
            'name.max' => 'اسم الإعداد لا يمكن أن يتجاوز 255 حرفاً',
            'driver.required' => 'نوع التخزين مطلوب',
            'driver.in' => 'نوع التخزين المحدد غير صالح',
            'config.array' => 'إعدادات التخزين يجب أن تكون مصفوفة',
            'priority.integer' => 'الأولوية يجب أن تكون رقماً',
            'priority.min' => 'الأولوية يجب أن تكون على الأقل 0',
            'cdn_url.url' => 'رابط CDN غير صالح',
            'file_types.array' => 'أنواع الملفات يجب أن تكون مصفوفة',
        ]);

        try {
            $driver = $validated['driver'];
            $configData = $request->input('config', []);

            // للتخزين المحلي: يمكن أن يكون config فارغاً، نضع قيمة افتراضية للمسار
            if ($driver === 'local') {
                if (empty($configData)) {
                    $configData = ['path' => 'public'];
                }
            } else {
                // لباقي الأنواع: إعدادات التخزين مطلوبة
                if (empty($configData)) {
                    return redirect()->back()
                        ->withErrors(['config' => 'إعدادات التخزين مطلوبة لهذا النوع من التخزين'])
                        ->withInput();
                }

                // التحقق من الحقول المطلوبة حسب نوع التخزين
                $requiredFields = $this->getRequiredFieldsForDriver($driver);

                foreach ($requiredFields as $field) {
                    if (empty($configData[$field])) {
                        return redirect()->back()
                            ->withErrors(['config' => "الحقل '{$field}' مطلوب لنوع التخزين '{$driver}'"])
                            ->withInput();
                    }
                }
            }

            $createData = [
                'name' => $validated['name'],
                'driver' => $driver,
                'config' => $configData,
                'priority' => $validated['priority'] ?? 0,
                'created_by' => Auth::id(),
                'is_active' => $request->has('is_active'),
                'redundancy' => $request->has('redundancy'),
                'cdn_url' => $request->input('cdn_url'),
                'file_types' => $request->input('file_types'),
            ];

            if ($request->has('pricing_config')) {
                $createData['pricing_config'] = [
                    'storage_cost_per_gb' => $request->input('pricing_config.storage_cost_per_gb'),
                    'upload_cost_per_gb' => $request->input('pricing_config.upload_cost_per_gb'),
                    'download_cost_per_gb' => $request->input('pricing_config.download_cost_per_gb'),
                ];
            }

            if ($request->has('monthly_budget')) {
                $createData['monthly_budget'] = $request->input('monthly_budget');
            }

            if ($request->has('cost_alert_threshold')) {
                $createData['cost_alert_threshold'] = $request->input('cost_alert_threshold');
            }

            AppStorageConfig::create($createData);

            return redirect()->route('admin.storage.index')
                           ->with('success', 'تم إضافة مكان التخزين بنجاح.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                           ->withErrors($e->errors())
                           ->withInput();
        } catch (\Exception $e) {
            Log::error('Error creating app storage config: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء إضافة مكان التخزين: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * تعديل الإعدادات
     */
    public function edit(AppStorageConfig $config)
    {
        $drivers = AppStorageConfig::DRIVERS;
        $config->load('creator');
        return view('admin.pages.app-storage.edit', compact('config', 'drivers'));
    }

    /**
     * تحديث الإعدادات
     */
    public function update(Request $request, AppStorageConfig $config)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|in:' . implode(',', array_keys(AppStorageConfig::DRIVERS)),
            'config' => 'nullable|array',
            'priority' => 'nullable|integer|min:0',
            'cdn_url' => 'nullable|url',
            'file_types' => 'nullable|array',
        ]);

        try {
            $driver = $validated['driver'];
            $configData = $request->input('config', []);

            // دمج config مع القيم القديمة (للحفاظ على passwords)
            $oldConfig = $config->getDecryptedConfig();

            foreach ($configData as $key => $value) {
                // إذا كان الحقل فارغاً وكان password/token، احتفظ بالقيمة القديمة
                if (empty($value) && (str_contains($key, 'password') || str_contains($key, 'token') || str_contains($key, 'secret') || str_contains($key, 'key'))) {
                    if (isset($oldConfig[$key])) {
                        $configData[$key] = $oldConfig[$key];
                    }
                }
            }

            // للتخزين المحلي: إذا بقي config فارغاً بعد الدمج، عيّن path افتراضي
            if ($driver === 'local' && empty($configData)) {
                $configData = ['path' => 'public'];
            }

            // لباقي الأنواع: تأكد من وجود إعدادات تخزين
            if ($driver !== 'local') {
                if (empty($configData)) {
                    return redirect()->back()
                        ->withErrors(['config' => 'إعدادات التخزين مطلوبة لهذا النوع من التخزين'])
                        ->withInput();
                }

                $requiredFields = $this->getRequiredFieldsForDriver($driver);

                foreach ($requiredFields as $field) {
                    if (empty($configData[$field]) && empty($oldConfig[$field] ?? null)) {
                        return redirect()->back()
                            ->withErrors(['config' => "الحقل '{$field}' مطلوب لنوع التخزين '{$driver}'"])
                            ->withInput();
                    }
                }
            }

            $updateData = [
                'name' => $validated['name'],
                'driver' => $driver,
                'config' => $configData,
                'priority' => $validated['priority'] ?? 0,
                'is_active' => $request->has('is_active'),
                'redundancy' => $request->has('redundancy'),
                'cdn_url' => $request->input('cdn_url'),
                'file_types' => $request->input('file_types'),
            ];

            if ($request->has('pricing_config')) {
                $updateData['pricing_config'] = [
                    'storage_cost_per_gb' => $request->input('pricing_config.storage_cost_per_gb'),
                    'upload_cost_per_gb' => $request->input('pricing_config.upload_cost_per_gb'),
                    'download_cost_per_gb' => $request->input('pricing_config.download_cost_per_gb'),
                ];
            }

            if ($request->has('monthly_budget')) {
                $updateData['monthly_budget'] = $request->input('monthly_budget');
            }

            if ($request->has('cost_alert_threshold')) {
                $updateData['cost_alert_threshold'] = $request->input('cost_alert_threshold');
            }

            $config->update($updateData);

            return redirect()->route('admin.storage.index')
                           ->with('success', 'تم تحديث إعدادات التخزين بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error updating app storage config: ' . $e->getMessage());
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء تحديث الإعدادات: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * حذف الإعدادات
     */
    public function destroy(AppStorageConfig $config)
    {
        try {
            $config->delete();

            return redirect()->route('admin.storage.index')
                           ->with('success', 'تم حذف إعدادات التخزين بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error deleting app storage config: ' . $e->getMessage());
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء حذف الإعدادات: ' . $e->getMessage());
        }
    }

    /**
     * اختبار الاتصال (لصفحة create - بدون config موجود)
     */
    public function testConnection(\Illuminate\Http\Request $request)
    {
        try {
            $request->validate([
                'driver' => 'required|string',
                'config' => 'required|array',
            ]);

            $driver = $request->input('driver');
            $configData = $request->input('config');

            // تنظيف وtrim للـ credentials (خاصة لـ Bunny Storage)
            if ($driver === 'bunny') {
                if (isset($configData['storage_zone'])) {
                    $configData['storage_zone'] = trim($configData['storage_zone']);
                }
                if (isset($configData['api_key'])) {
                    $configData['api_key'] = trim($configData['api_key']);
                }
                if (isset($configData['pull_zone'])) {
                    $configData['pull_zone'] = trim($configData['pull_zone']);
                }
                
                // التحقق من أن الـ credentials غير فارغة
                if (empty($configData['storage_zone'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Storage Zone Name مطلوب ولا يمكن أن يكون فارغاً',
                    ], 422);
                }
                
                if (empty($configData['api_key'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'API Key (FTP Password) مطلوب ولا يمكن أن يكون فارغاً',
                    ], 422);
                }
            }

            // التحقق من الإعدادات المطلوبة حسب نوع التخزين
            $validationErrors = $this->validateStorageConfig($driver, $configData);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'إعدادات غير مكتملة: ' . implode(', ', $validationErrors),
                ], 422);
            }

            // استخدام AppStorageFactory مباشرة لإنشاء disk للاختبار
            $diskName = 'test_storage_' . md5(json_encode([$driver, $configData]));
            
            $diskConfig = match($driver) {
                'local' => [
                    'driver' => 'local',
                    'root' => storage_path('app/' . ($configData['path'] ?? 'public')),
                    'visibility' => 'public',
                    'throw' => false,
                ],
                'google_drive' => [
                    'driver' => 'google',
                    'clientId' => $configData['client_id'] ?? '',
                    'clientSecret' => $configData['client_secret'] ?? '',
                    'refreshToken' => $configData['refresh_token'] ?? '',
                    'folder' => $configData['folder_id'] ?? null,
                    'throw' => true, // إجبار رمي exceptions لإظهار رسالة الخطأ الفعلية
                ],
                's3' => [
                    'driver' => 's3',
                    'key' => $configData['access_key_id'] ?? '',
                    'secret' => $configData['secret_access_key'] ?? '',
                    'region' => $configData['region'] ?? 'us-east-1',
                    'bucket' => $configData['bucket'] ?? '',
                    'url' => $configData['url'] ?? null,
                    'endpoint' => $configData['endpoint'] ?? null,
                    'use_path_style_endpoint' => $configData['use_path_style'] ?? false,
                    'throw' => false,
                ],
                'cloudflare_r2' => [
                    'driver' => 's3',
                    'key' => $configData['access_key_id'] ?? '',
                    'secret' => $configData['secret_access_key'] ?? '',
                    'region' => 'auto',
                    'bucket' => $configData['bucket'] ?? '',
                    'endpoint' => "https://{$configData['account_id']}.r2.cloudflarestorage.com",
                    'use_path_style_endpoint' => true,
                    'throw' => false,
                ],
                'digitalocean' => [
                    'driver' => 's3',
                    'key' => $configData['access_key_id'] ?? '',
                    'secret' => $configData['secret_access_key'] ?? '',
                    'region' => $configData['region'] ?? 'nyc3',
                    'bucket' => $configData['bucket'] ?? '',
                    'endpoint' => 'https://' . ($configData['region'] ?? 'nyc3') . '.digitaloceanspaces.com',
                    'use_path_style_endpoint' => true,
                    'throw' => false,
                ],
                'wasabi' => [
                    'driver' => 's3',
                    'key' => $configData['access_key_id'] ?? '',
                    'secret' => $configData['secret_access_key'] ?? '',
                    'region' => $configData['region'] ?? 'us-east-1',
                    'bucket' => $configData['bucket'] ?? '',
                    'endpoint' => 'https://s3.' . ($configData['region'] ?? 'us-east-1') . '.wasabisys.com',
                    'use_path_style_endpoint' => true,
                    'throw' => false,
                ],
                'backblaze' => [
                    'driver' => 's3',
                    'key' => $configData['access_key_id'] ?? '',
                    'secret' => $configData['secret_access_key'] ?? '',
                    'region' => $configData['region'] ?? 'us-west-000',
                    'bucket' => $configData['bucket'] ?? '',
                    'endpoint' => 'https://s3.' . ($configData['region'] ?? 'us-west-000') . '.backblazeb2.com',
                    'use_path_style_endpoint' => true,
                    'throw' => false,
                ],
                'bunny' => [
                    'driver' => 'bunnycdn',
                    'storage_zone' => trim($configData['storage_zone'] ?? ''),
                    'api_key' => $configData['api_key'] ?? '',
                    'region' => $configData['region'] ?? 'de',
                    'pull_zone' => $configData['pull_zone'] ?? '',
                    'throw' => true,
                ],
                default => throw new \Exception("نوع التخزين غير مدعوم: {$driver}"),
            };

            // تسجيل الـ disk ديناميكياً
            \Illuminate\Support\Facades\Config::set("filesystems.disks.{$diskName}", $diskConfig);
            
            // محاولة كتابة ملف اختبار مع معالجة الأخطاء المفصلة
            $testPath = 'test_' . time() . '.txt';
            $testContent = 'test';
            $disk = \Illuminate\Support\Facades\Storage::disk($diskName);
            
            try {
                $result = $disk->put($testPath, $testContent);
                
                if ($result) {
                    // حذف الملف الاختباري
                    try {
                        $disk->delete($testPath);
                    } catch (\Exception $deleteException) {
                        Log::warning('Failed to delete test file: ' . $deleteException->getMessage());
                        // لا نعتبر هذا خطأ فادحاً
                    }
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'الاتصال ناجح ✓',
                    ]);
                }
                
                // إذا كان put() يعيد false بدون exception
                return response()->json([
                    'success' => false,
                    'message' => 'فشل الاتصال: لم يتمكن من كتابة الملف الاختباري. يرجى التحقق من الإعدادات والأذونات.',
                ]);
            } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل الاتصال: الملف أو المجلد غير موجود. ' . $e->getMessage(),
                ], 500);
            } catch (\Illuminate\Contracts\Filesystem\FilesystemException $e) {
                $errorMessage = $e->getMessage();
                
                // تحسين رسائل الخطأ الشائعة
                if (str_contains(strtolower($errorMessage), 'unauthorized') || str_contains(strtolower($errorMessage), '401')) {
                    if ($driver === 'bunny') {
                        $errorMessage = 'فشل المصادقة (401 Unauthorized): يرجى التحقق من: 1) Storage Zone Name صحيح (بدون مسافات في البداية أو النهاية) 2) API Key (FTP Password) صحيح ومفعل 3) Region يطابق Storage Zone في BunnyCDN Dashboard';
                    } else {
                        $errorMessage = 'فشل المصادقة: يرجى التحقق من Client ID و Client Secret و Refresh Token';
                    }
                } elseif (str_contains(strtolower($errorMessage), 'authentication')) {
                    if ($driver === 'bunny') {
                        $errorMessage = 'فشل المصادقة: يرجى التحقق من Storage Zone Name و API Key';
                    } else {
                        $errorMessage = 'فشل المصادقة: يرجى التحقق من Client ID و Client Secret و Refresh Token';
                    }
                } elseif (str_contains(strtolower($errorMessage), 'permission') || str_contains(strtolower($errorMessage), 'access')) {
                    $errorMessage = 'فشل الوصول: يرجى التحقق من الأذونات (Permissions)';
                } elseif (str_contains(strtolower($errorMessage), 'quota') || str_contains(strtolower($errorMessage), 'storage')) {
                    $errorMessage = 'فشل التخزين: تم تجاوز المساحة المتاحة';
                } elseif (str_contains(strtolower($errorMessage), 'network') || str_contains(strtolower($errorMessage), 'timeout')) {
                    $errorMessage = 'فشل الاتصال: مشكلة في الشبكة أو انتهت مهلة الاتصال';
                }
                
                Log::error('Test connection filesystem error: ' . $e->getMessage(), [
                    'driver' => $driver,
                    'error_class' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'فشل الاتصال: ' . $errorMessage,
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة: ' . implode(', ', $e->errors()['driver'] ?? []),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Test connection failed: ' . $e->getMessage(), [
                'driver' => $request->input('driver'),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $errorMessage = $e->getMessage();
            
            // تحسين رسائل الخطأ العامة
            if (str_contains($errorMessage, 'not found') || str_contains($errorMessage, 'missing')) {
                $errorMessage = 'إعدادات ناقصة: يرجى التأكد من ملء جميع الحقول المطلوبة';
            }
            
            return response()->json([
                'success' => false,
                'message' => 'فشل الاتصال: ' . $errorMessage,
            ], 500);
        }
    }

    /**
     * التحقق من الإعدادات المطلوبة حسب نوع التخزين
     */
    private function validateStorageConfig(string $driver, array $configData): array
    {
        $errors = [];

        switch ($driver) {
            case 'google_drive':
                if (empty($configData['client_id'])) {
                    $errors[] = 'Client ID مطلوب';
                }
                if (empty($configData['client_secret'])) {
                    $errors[] = 'Client Secret مطلوب';
                }
                if (empty($configData['refresh_token'])) {
                    $errors[] = 'Refresh Token مطلوب';
                }
                break;

            case 's3':
                if (empty($configData['access_key_id'])) {
                    $errors[] = 'Access Key ID مطلوب';
                }
                if (empty($configData['secret_access_key'])) {
                    $errors[] = 'Secret Access Key مطلوب';
                }
                if (empty($configData['bucket'])) {
                    $errors[] = 'Bucket مطلوب';
                }
                break;

            case 'bunny':
                if (empty($configData['storage_zone'])) {
                    $errors[] = 'Storage Zone مطلوب';
                }
                if (empty($configData['api_key'])) {
                    $errors[] = 'API Key مطلوب';
                }
                break;

            case 'local':
                // لا توجد إعدادات مطلوبة للتخزين المحلي
                break;

            default:
                // التحقق العام للحقول المطلوبة
                break;
        }

        return $errors;
    }

    /**
     * اختبار الاتصال (لصفحة edit - مع config موجود)
     */
    public function test(AppStorageConfig $config)
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
     * الحصول على الحقول المطلوبة حسب نوع التخزين
     */
    private function getRequiredFieldsForDriver(string $driver): array
    {
        return match($driver) {
            'local' => [], // لا توجد حقول مطلوبة للتخزين المحلي
            's3', 'digitalocean', 'wasabi', 'backblaze' => ['access_key_id', 'secret_access_key', 'bucket'],
            'cloudflare_r2' => ['account_id', 'access_key_id', 'secret_access_key', 'bucket'],
            'google_drive' => ['client_id', 'client_secret', 'refresh_token'],
            'dropbox' => ['access_token'],
            'ftp', 'sftp' => ['host', 'username', 'password'],
            'azure' => ['account_name', 'account_key', 'container'],
            'bunny' => ['storage_zone', 'api_key'],
            default => [],
        };
    }
}
