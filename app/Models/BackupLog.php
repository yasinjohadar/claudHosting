<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'backup_id',
        'level',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    /**
     * المستويات
     */
    public const LEVELS = [
        'info' => 'معلومات',
        'warning' => 'تحذير',
        'error' => 'خطأ',
    ];

    /**
     * العلاقة مع النسخة
     */
    public function backup()
    {
        return $this->belongsTo(Backup::class, 'backup_id');
    }
}
