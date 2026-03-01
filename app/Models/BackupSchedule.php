<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BackupSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'backup_type',
        'frequency',
        'time',
        'days_of_week',
        'day_of_month',
        'storage_drivers',
        'compression_types',
        'retention_days',
        'is_active',
        'last_run_at',
        'next_run_at',
        'created_by',
    ];

    protected $casts = [
        'time' => 'string',
        'days_of_week' => 'array',
        'storage_drivers' => 'array',
        'compression_types' => 'array',
        'retention_days' => 'integer',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    /**
     * أنواع المحتوى
     */
    public const BACKUP_TYPES = [
        'full' => 'كامل',
        'database' => 'قاعدة البيانات',
        'files' => 'الملفات',
        'config' => 'الإعدادات',
    ];

    /**
     * التكرارات
     */
    public const FREQUENCIES = [
        'daily' => 'يومي',
        'weekly' => 'أسبوعي',
        'monthly' => 'شهري',
        'custom' => 'مخصص',
    ];

    /**
     * العلاقة مع منشئ الجدولة
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع النسخ
     */
    public function backups()
    {
        return $this->hasMany(Backup::class, 'schedule_id');
    }

    /**
     * التحقق من وجوب التشغيل
     */
    public function shouldRun(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->next_run_at) {
            return false;
        }

        return now()->gte($this->next_run_at);
    }

    /**
     * حساب وقت التشغيل التالي
     */
    public function calculateNextRun(): Carbon
    {
        $time = Carbon::parse($this->time);
        $now = now();

        return match($this->frequency) {
            'daily' => $now->copy()->setTimeFromTimeString($this->time)->addDay(),
            'weekly' => $this->calculateNextWeeklyRun(),
            'monthly' => $this->calculateNextMonthlyRun(),
            'custom' => $now->copy()->setTimeFromTimeString($this->time)->addDay(),
            default => $now->copy()->addDay(),
        };
    }

    /**
     * حساب وقت التشغيل الأسبوعي التالي
     */
    private function calculateNextWeeklyRun(): Carbon
    {
        $time = Carbon::parse($this->time);
        $now = now();
        $daysOfWeek = $this->days_of_week ?? [];

        if (empty($daysOfWeek)) {
            return $now->copy()->addWeek();
        }

        $currentDay = $now->dayOfWeek;
        $nextDay = null;

        foreach ($daysOfWeek as $day) {
            if ($day > $currentDay) {
                $nextDay = $day;
                break;
            }
        }

        if ($nextDay === null) {
            $nextDay = $daysOfWeek[0];
            return $now->copy()->next($this->getDayName($nextDay))->setTimeFromTimeString($this->time);
        }

        return $now->copy()->next($this->getDayName($nextDay))->setTimeFromTimeString($this->time);
    }

    /**
     * حساب وقت التشغيل الشهري التالي
     */
    private function calculateNextMonthlyRun(): Carbon
    {
        $time = Carbon::parse($this->time);
        $now = now();
        $dayOfMonth = $this->day_of_month ?? 1;

        $nextRun = $now->copy()->day($dayOfMonth)->setTimeFromTimeString($this->time);

        if ($nextRun->isPast()) {
            $nextRun->addMonth();
        }

        return $nextRun;
    }

    /**
     * الحصول على اسم اليوم
     */
    private function getDayName(int $day): string
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$day] ?? 'Monday';
    }

    /**
     * تنفيذ الجدولة
     */
    public function execute(): Backup
    {
        // سيتم تنفيذ هذا في Service
        throw new \Exception('Not implemented');
    }
}
