<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupSchedule;
use App\Services\Backup\BackupScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BackupScheduleController extends Controller
{
    public function __construct(
        private BackupScheduleService $scheduleService
    ) {}

    /**
     * قائمة الجدولات
     */
    public function index()
    {
        $schedules = BackupSchedule::with(['creator', 'backups'])
                                  ->latest()
                                  ->paginate(20);

        return view('admin.pages.backup-schedules.index', compact('schedules'));
    }

    /**
     * إنشاء جدولة
     */
    public function create()
    {
        $backupTypes = BackupSchedule::BACKUP_TYPES;
        $frequencies = BackupSchedule::FREQUENCIES;
        $storageConfigs = \App\Models\AppStorageConfig::where('is_active', true)->get();
        $compressionTypes = \App\Models\Backup::COMPRESSION_TYPES;

        return view('admin.pages.backup-schedules.create', compact(
            'backupTypes',
            'frequencies',
            'storageConfigs',
            'compressionTypes'
        ));
    }

    /**
     * حفظ الجدولة
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'backup_type' => 'required|in:' . implode(',', array_keys(BackupSchedule::BACKUP_TYPES)),
            'frequency' => 'required|in:' . implode(',', array_keys(BackupSchedule::FREQUENCIES)),
            'time' => 'required|date_format:H:i',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'storage_config_id' => 'required|integer|exists:app_storage_configs,id',
            'compression_types' => 'required|array|min:1',
            'compression_types.*' => 'in:' . implode(',', array_keys(\App\Models\Backup::COMPRESSION_TYPES)),
            'retention_days' => 'required|integer|min:1|max:365',
        ], [
            'name.required' => 'اسم الجدولة مطلوب',
            'name.string' => 'اسم الجدولة يجب أن يكون نصاً',
            'name.max' => 'اسم الجدولة لا يمكن أن يتجاوز 255 حرفاً',
            'backup_type.required' => 'نوع النسخ مطلوب',
            'backup_type.in' => 'نوع النسخ المحدد غير صالح',
            'frequency.required' => 'التكرار مطلوب',
            'frequency.in' => 'التكرار المحدد غير صالح',
            'time.required' => 'الوقت مطلوب',
            'time.date_format' => 'صيغة الوقت غير صحيحة (يجب أن تكون HH:MM)',
            'days_of_week.array' => 'أيام الأسبوع يجب أن تكون مصفوفة',
            'days_of_week.*.integer' => 'يوم الأسبوع يجب أن يكون رقماً',
            'days_of_week.*.min' => 'يوم الأسبوع يجب أن يكون بين 0 و 6',
            'days_of_week.*.max' => 'يوم الأسبوع يجب أن يكون بين 0 و 6',
            'day_of_month.integer' => 'يوم الشهر يجب أن يكون رقماً',
            'day_of_month.min' => 'يوم الشهر يجب أن يكون بين 1 و 31',
            'day_of_month.max' => 'يوم الشهر يجب أن يكون بين 1 و 31',
            'storage_config_id.required' => 'مكان التخزين مطلوب',
            'storage_config_id.integer' => 'مكان التخزين يجب أن يكون رقماً',
            'storage_config_id.exists' => 'مكان التخزين المحدد غير موجود',
            'compression_types.required' => 'أنواع الضغط مطلوبة',
            'compression_types.array' => 'أنواع الضغط يجب أن تكون مصفوفة',
            'compression_types.min' => 'يجب اختيار نوع ضغط واحد على الأقل',
            'compression_types.*.in' => 'نوع الضغط المحدد غير صالح',
            'retention_days.required' => 'أيام الاحتفاظ مطلوبة',
            'retention_days.integer' => 'أيام الاحتفاظ يجب أن تكون رقماً',
            'retention_days.min' => 'أيام الاحتفاظ يجب أن تكون على الأقل 1',
            'retention_days.max' => 'أيام الاحتفاظ لا يمكن أن تتجاوز 365',
        ]);

        try {
            // تحويل storage_config_id إلى storage_drivers (array) للتوافق مع الـ model
            $storageConfig = \App\Models\AppStorageConfig::find($validated['storage_config_id']);
            if (!$storageConfig || !$storageConfig->is_active) {
                return redirect()->back()
                               ->with('error', 'مكان التخزين المحدد غير موجود أو غير نشط')
                               ->withInput();
            }

            // إضافة storage_drivers للتوافق مع الـ model الحالي
            $validated['storage_drivers'] = [$storageConfig->driver];

            $schedule = $this->scheduleService->createSchedule(array_merge($validated, [
                'created_by' => Auth::id(),
            ]));

            return redirect()->route('admin.backup-schedules.index')
                           ->with('success', 'تم إنشاء الجدولة بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error creating backup schedule: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء إنشاء الجدولة: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * تعديل جدولة
     */
    public function edit(BackupSchedule $schedule)
    {
        $backupTypes = BackupSchedule::BACKUP_TYPES;
        $frequencies = BackupSchedule::FREQUENCIES;
        $storageConfigs = \App\Models\AppStorageConfig::where('is_active', true)->get();
        $compressionTypes = \App\Models\Backup::COMPRESSION_TYPES;

        return view('admin.pages.backup-schedules.edit', compact(
            'schedule',
            'backupTypes',
            'frequencies',
            'storageConfigs',
            'compressionTypes'
        ));
    }

    /**
     * تحديث الجدولة
     */
    public function update(Request $request, BackupSchedule $schedule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'backup_type' => 'required|in:' . implode(',', array_keys(BackupSchedule::BACKUP_TYPES)),
            'frequency' => 'required|in:' . implode(',', array_keys(BackupSchedule::FREQUENCIES)),
            'time' => 'required|date_format:H:i',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'storage_config_id' => 'required|integer|exists:app_storage_configs,id',
            'compression_types' => 'required|array|min:1',
            'compression_types.*' => 'in:' . implode(',', array_keys(\App\Models\Backup::COMPRESSION_TYPES)),
            'retention_days' => 'required|integer|min:1|max:365',
        ], [
            'name.required' => 'اسم الجدولة مطلوب',
            'name.string' => 'اسم الجدولة يجب أن يكون نصاً',
            'name.max' => 'اسم الجدولة لا يمكن أن يتجاوز 255 حرفاً',
            'backup_type.required' => 'نوع النسخ مطلوب',
            'backup_type.in' => 'نوع النسخ المحدد غير صالح',
            'frequency.required' => 'التكرار مطلوب',
            'frequency.in' => 'التكرار المحدد غير صالح',
            'time.required' => 'الوقت مطلوب',
            'time.date_format' => 'صيغة الوقت غير صحيحة (يجب أن تكون HH:MM)',
            'days_of_week.array' => 'أيام الأسبوع يجب أن تكون مصفوفة',
            'days_of_week.*.integer' => 'يوم الأسبوع يجب أن يكون رقماً',
            'days_of_week.*.min' => 'يوم الأسبوع يجب أن يكون بين 0 و 6',
            'days_of_week.*.max' => 'يوم الأسبوع يجب أن يكون بين 0 و 6',
            'day_of_month.integer' => 'يوم الشهر يجب أن يكون رقماً',
            'day_of_month.min' => 'يوم الشهر يجب أن يكون بين 1 و 31',
            'day_of_month.max' => 'يوم الشهر يجب أن يكون بين 1 و 31',
            'storage_config_id.required' => 'مكان التخزين مطلوب',
            'storage_config_id.integer' => 'مكان التخزين يجب أن يكون رقماً',
            'storage_config_id.exists' => 'مكان التخزين المحدد غير موجود',
            'compression_types.required' => 'أنواع الضغط مطلوبة',
            'compression_types.array' => 'أنواع الضغط يجب أن تكون مصفوفة',
            'compression_types.min' => 'يجب اختيار نوع ضغط واحد على الأقل',
            'compression_types.*.in' => 'نوع الضغط المحدد غير صالح',
            'retention_days.required' => 'أيام الاحتفاظ مطلوبة',
            'retention_days.integer' => 'أيام الاحتفاظ يجب أن تكون رقماً',
            'retention_days.min' => 'أيام الاحتفاظ يجب أن تكون على الأقل 1',
            'retention_days.max' => 'أيام الاحتفاظ لا يمكن أن تتجاوز 365',
        ]);

        try {
            // تحويل storage_config_id إلى storage_drivers (array) للتوافق مع الـ model
            $storageConfig = \App\Models\AppStorageConfig::find($validated['storage_config_id']);
            if (!$storageConfig || !$storageConfig->is_active) {
                return redirect()->back()
                               ->with('error', 'مكان التخزين المحدد غير موجود أو غير نشط')
                               ->withInput();
            }

            // إضافة storage_drivers للتوافق مع الـ model الحالي
            $validated['storage_drivers'] = [$storageConfig->driver];

            $this->scheduleService->updateSchedule($schedule, $validated);

            return redirect()->route('admin.backup-schedules.index')
                           ->with('success', 'تم تحديث الجدولة بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error updating backup schedule: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء تحديث الجدولة: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * حذف الجدولة
     */
    public function destroy(BackupSchedule $schedule)
    {
        try {
            $this->scheduleService->deleteSchedule($schedule);

            return redirect()->route('admin.backup-schedules.index')
                           ->with('success', 'تم حذف الجدولة بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error deleting backup schedule: ' . $e->getMessage());
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء حذف الجدولة: ' . $e->getMessage());
        }
    }

    /**
     * تشغيل جدولة يدوياً
     */
    public function execute(BackupSchedule $schedule)
    {
        try {
            $backup = $this->scheduleService->executeSchedule($schedule);

            return redirect()->route('admin.backups.show', $backup)
                           ->with('success', 'تم تشغيل الجدولة بنجاح.');
        } catch (\Exception $e) {
            Log::error('Error executing backup schedule: ' . $e->getMessage());
            return redirect()->back()
                           ->with('error', 'حدث خطأ أثناء تشغيل الجدولة: ' . $e->getMessage());
        }
    }

    /**
     * تفعيل/إلغاء تفعيل
     */
    public function toggleActive(BackupSchedule $schedule)
    {
        try {
            $schedule->update(['is_active' => !$schedule->is_active]);

            return redirect()->back()
                           ->with('success', 'تم تحديث حالة الجدولة بنجاح.');
        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
}
