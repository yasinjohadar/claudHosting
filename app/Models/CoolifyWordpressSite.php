<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CoolifyWordpressSite extends Model
{
    public const STATUSES = [
        'pending' => 'معلق',
        'provisioning' => 'جاري الإنشاء',
        'running' => 'يعمل',
        'failed' => 'فاشل',
    ];

    public const PROJECT_MODES = [
        'new' => 'مشروع جديد',
        'shared' => 'مشروع مشترك',
    ];

    protected $fillable = [
        'uuid',
        'display_name',
        'slug',
        'project_mode',
        'project_uuid',
        'project_name',
        'service_uuid',
        'server_uuid',
        'environment_name',
        'public_url',
        'admin_url',
        'status',
        'error_message',
        'description',
        'metadata',
        'created_by',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function slugFromName(string $name): string
    {
        $slug = Str::slug($name, '-');
        if ($slug === '') {
            $slug = 'site-'.Str::lower(Str::random(6));
        }

        return substr($slug, 0, 63);
    }

    public static function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 0;
        while (static::query()->where('slug', $slug)->exists()) {
            $i++;
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
