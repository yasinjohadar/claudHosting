<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\Backup\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    public function __construct(
        private BackupService $backupService
    ) {}

    /**
     * قائمة النسخ
     */
    public function index(Request $request)
    {
        $query = Backup::with(['creator', 'schedule']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('backup_type')) {
            $query->where('backup_type', $request->backup_type);
        }

        if ($request->filled('storage_driver')) {
            $query->where('storage_driver', $request->storage_driver);
        }

        $backups = $query->latest()->paginate(20);
        $stats = $this->backupService->getBackupStats();

        return view('admin.pages.backups.index', compact('backups', 'stats'));
    }

    /**
     * إنشاء نسخة يدوية
     */
    public function create()
    {
        $backupTypes = Backup::BACKUP_TYPES;
        $compressionTypes = Backup::COMPRESSION_TYPES;
        // استخدام AppStorageConfig بدلاً من BackupStorageConfig (نظام موحد)
        $storageConfigs = \App\Models\AppStorageConfig::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        return view('admin.pages.backups.create', compact('backupTypes', 'compressionTypes', 'storageConfigs'));
    }

    /**
     * حفظ النسخة
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'backup_type' => 'required|in:' . implode(',', array_keys(Backup::BACKUP_TYPES)),
            'storage_config_id' => 'required|integer|exists:app_storage_configs,id',
            'compression_type' => 'required|in:' . implode(',', array_keys(Backup::COMPRESSION_TYPES)),
            'retention_days' => 'required|integer|min:1|max:365',
        ], [
            'name.required' => 'اسم النسخة مطلوب',
            'name.string' => 'اسم النسخة يجب أن يكون نصاً',
            'name.max' => 'اسم النسخة لا يمكن أن يتجاوز 255 حرفاً',
            'backup_type.required' => 'نوع النسخ مطلوب',
            'backup_type.in' => 'نوع النسخ المحدد غير صالح',
            'storage_config_id.required' => 'مكان التخزين مطلوب',
            'storage_config_id.integer' => 'مكان التخزين يجب أن يكون رقماً',
            'storage_config_id.exists' => 'مكان التخزين المحدد غير موجود',
            'compression_type.required' => 'نوع الضغط مطلوب',
            'compression_type.in' => 'نوع الضغط المحدد غير صالح',
            'retention_days.required' => 'أيام الاحتفاظ مطلوبة',
            'retention_days.integer' => 'أيام الاحتفاظ يجب أن تكون رقماً',
            'retention_days.min' => 'أيام الاحتفاظ يجب أن تكون على الأقل 1',
            'retention_days.max' => 'أيام الاحتفاظ لا يمكن أن تتجاوز 365',
        ]);

        // التحقق من وجود مكان التخزين ونشاطه
        $storageConfig = \App\Models\AppStorageConfig::where('id', $validated['storage_config_id'])
            ->where('is_active', true)
            ->first();

        if (!$storageConfig) {
            return redirect()->back()
                ->with('error', 'مكان التخزين المحدد غير موجود أو غير نشط')
                ->withInput();
        }

        try {
            // إنشاء سجل النسخة أولاً
            $backup = Backup::create([
                'name' => $validated['name'],
                'type' => 'manual',
                'backup_type' => $validated['backup_type'],
                'storage_driver' => $storageConfig->driver,
                'storage_config_id' => $storageConfig->id,
                'storage_path' => null,
                'file_path' => null,
                'compression_type' => $validated['compression_type'],
                'status' => 'pending',
                'retention_days' => $validated['retention_days'],
                'created_by' => Auth::id(),
                'schedule_id' => null,
            ]);

            $backup->update([
                'expires_at' => $backup->calculateExpiresAt(),
            ]);

            // تشغيل Job لإنشاء النسخة
            // في بيئة التطوير، استخدم dispatchSync لضمان التشغيل الفوري
            // في الإنتاج، استخدم dispatch للعمل بشكل غير متزامن
            $jobOptions = [
                'name' => $validated['name'],
                'type' => 'manual',
                'backup_type' => $validated['backup_type'],
                'storage_config_id' => $storageConfig->id,
                'storage_driver' => $storageConfig->driver,
                'compression_type' => $validated['compression_type'],
                'retention_days' => $validated['retention_days'],
                'created_by' => Auth::id(),
            ];

            if (config('app.env') === 'local' || config('app.debug')) {
                // في بيئة التطوير، شغّل Job بشكل متزامن
                \App\Jobs\CreateBackupJob::dispatchSync($backup, $jobOptions);
                return redirect()->route('admin.backups.show', $backup)
                               ->with('success', 'تم إنشاء النسخة الاحتياطية بنجاح.');
            } else {
                // في الإنتاج، شغّل Job بشكل غير متزامن
                \App\Jobs\CreateBackupJob::dispatch($backup, $jobOptions);
                return redirect()->route('admin.backups.show', $backup)
                               ->with('info', 'تم بدء عملية إنشاء النسخة الاحتياطية. جاري المعالجة...');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error creating backup: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء إنشاء النسخة: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * عرض تفاصيل النسخة
     */
    public function show(Backup $backup)
    {
        $backup->load(['creator', 'schedule', 'logs', 'storageConfig']);
        return view('admin.pages.backups.show', compact('backup'));
    }

    /**
     * الحصول على حالة النسخة (لـ AJAX polling)
     */
    public function status(Backup $backup)
    {
        $backup->load(['logs']);
        
        return response()->json([
            'status' => $backup->status,
            'file_size' => $backup->file_size,
            'file_size_formatted' => $backup->getFileSize(),
            'completed_at' => $backup->completed_at?->format('Y-m-d H:i:s'),
            'error_message' => $backup->error_message,
            'duration' => $backup->duration,
            'logs_count' => $backup->logs->count(),
            'latest_log' => $backup->logs->last()?->message,
            'logs' => $backup->logs->map(function($log) {
                return [
                    'id' => $log->id,
                    'level' => $log->level,
                    'message' => $log->message,
                    'created_at' => $log->created_at->format('H:i:s'),
                ];
            })->values(),
        ]);
    }

    /**
     * تشغيل Job النسخة يدوياً
     */
    public function run(Backup $backup)
    {
        // التحقق من أن النسخة في حالة pending أو failed
        if (!in_array($backup->status, ['pending', 'failed'])) {
            return redirect()->back()
                           ->with('error', 'لا يمكن تشغيل النسخة. الحالة الحالية: ' . $backup->status);
        }

        try {
            // إعداد خيارات Job
            $jobOptions = [
                'name' => $backup->name,
                'type' => $backup->type,
                'backup_type' => $backup->backup_type,
                'storage_config_id' => $backup->storage_config_id,
                'storage_driver' => $backup->storage_driver,
                'compression_type' => $backup->compression_type,
                'retention_days' => $backup->retention_days,
                'created_by' => $backup->created_by,
            ];

            // تشغيل Job (متزامن في التطوير، غير متزامن في الإنتاج)
            if (config('app.env') === 'local' || config('app.debug')) {
                \App\Jobs\CreateBackupJob::dispatchSync($backup, $jobOptions);
                return redirect()->back()
                               ->with('success', 'تم تشغيل النسخة الاحتياطية بنجاح.');
            } else {
                \App\Jobs\CreateBackupJob::dispatch($backup, $jobOptions);
                return redirect()->back()
                               ->with('info', 'تم بدء تشغيل النسخة الاحتياطية. جاري المعالجة...');
            }
        } catch (\Exception $e) {
            Log::error('Error running backup manually: ' . $e->getMessage(), [
                'backup_id' => $backup->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء تشغيل النسخة: ' . $e->getMessage());
        }
    }

    /**
     * تحميل النسخة
     */
    public function download(Backup $backup)
    {
        try {
            return $this->backupService->downloadBackup($backup);
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء تحميل النسخة: ' . $e->getMessage());
        }
    }

    /**
     * استعادة النسخة
     */
    public function restore(Request $request, Backup $backup)
    {
        // قبول confirm كـ true أو 1 في JSON request
        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            // التحقق من وجود confirm
            if (!$request->has('confirm')) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تأكيد عملية الاستعادة',
                ], 422);
            }
            
            // التحقق من أن confirm هو true أو 1
            $confirm = $request->input('confirm');
            if (!$request->boolean('confirm') && $confirm !== '1' && $confirm !== 1 && $confirm !== 'true') {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تأكيد عملية الاستعادة',
                ], 422);
            }
        } else {
            // للـ form submission العادي
            $validated = $request->validate([
                'confirm' => 'required|accepted',
            ]);
        }

        // إذا كان AJAX request، تنفيذ مباشر بدلاً من Job
        if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            try {
                Log::info('Starting backup restore', [
                    'backup_id' => $backup->id,
                    'backup_name' => $backup->name,
                    'user_id' => Auth::id(),
                ]);
                
                // تنفيذ مباشر بدلاً من Job لضمان التنفيذ الفوري
                $result = $this->backupService->restoreBackup($backup, $request->all());
                
                if ($result) {
                    Log::info('Backup restore completed successfully', [
                        'backup_id' => $backup->id,
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'تم استعادة النسخة الاحتياطية بنجاح.',
                        'backup_id' => $backup->id,
                    ]);
                } else {
                    throw new \Exception('فشلت عملية الاستعادة بدون خطأ محدد');
                }
            } catch (\Exception $e) {
                Log::error('Error restoring backup: ' . $e->getMessage(), [
                    'backup_id' => $backup->id,
                    'backup_name' => $backup->name,
                    'user_id' => Auth::id(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء استعادة النسخة: ' . $e->getMessage(),
                ], 500);
            }
        }

        // Fallback للـ form submission العادي
        try {
            Log::info('Starting backup restore (form submission)', [
                'backup_id' => $backup->id,
                'backup_name' => $backup->name,
                'user_id' => Auth::id(),
            ]);
            
            $result = $this->backupService->restoreBackup($backup, $request->all());
            
            if ($result) {
                Log::info('Backup restore completed successfully (form submission)', [
                    'backup_id' => $backup->id,
                ]);
                
                return redirect()->route('admin.backups.index')
                               ->with('success', 'تم استعادة النسخة الاحتياطية بنجاح.');
            } else {
                throw new \Exception('فشلت عملية الاستعادة بدون خطأ محدد');
            }
        } catch (\Exception $e) {
            Log::error('Error restoring backup (form submission): ' . $e->getMessage(), [
                'backup_id' => $backup->id,
                'backup_name' => $backup->name,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء استعادة النسخة: ' . $e->getMessage());
        }
    }

    /**
     * حذف النسخة
     */
    public function destroy(Backup $backup)
    {
        try {
            $this->backupService->deleteBackup($backup);

            return redirect()->route('admin.backups.index')
                           ->with('success', 'تم حذف النسخة الاحتياطية بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error deleting backup: ' . $e->getMessage());
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء حذف النسخة: ' . $e->getMessage());
        }
    }

    /**
     * إحصائيات
     */
    public function stats()
    {
        $stats = $this->backupService->getBackupStats();
        return response()->json($stats);
    }
}
