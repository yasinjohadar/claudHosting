<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /**
     * الحصول على قيمة مفتاح واحد.
     */
    public static function getByKey(string $key): ?string
    {
        $all = static::getAllKeyValue();
        return $all[$key] ?? null;
    }

    /**
     * الحصول على كل الإعدادات كمصفوفة [key => value] مع كاش لمدة 10 دقائق.
     */
    public static function getAllKeyValue(): array
    {
        return Cache::remember('app_settings_key_value', 600, function () {
            return static::query()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * تعيين قيمة لمفتاح (إنشاء أو تحديث).
     */
    public static function set(string $key, ?string $value, ?string $group = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
        Cache::forget('app_settings_key_value');
    }

    /**
     * مسح الكاش بعد التحديث الجماعي.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('app_settings_key_value');
        });
        static::deleted(function () {
            Cache::forget('app_settings_key_value');
        });
    }
}
