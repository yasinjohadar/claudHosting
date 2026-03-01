<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = ['key', 'value', 'group', 'type'];

    /**
     * Scope by key.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Scope by group.
     */
    public function scopeOfGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Set or update a setting.
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general'): self
    {
        $setting = static::query()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        $value = is_string($value) ? $value : json_encode($value);

        if ($setting) {
            $setting->update(['value' => $value, 'type' => $type]);
            return $setting;
        }

        return static::create([
            'key' => $key,
            'value' => $value,
            'group' => $group,
            'type' => $type,
        ]);
    }
}
