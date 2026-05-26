<?php

use App\Models\Setting;
use App\Services\HeroSettingsService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (Setting::query()->where('key', HeroSettingsService::SETTING_KEY)->exists()) {
            return;
        }

        $defaults = app(HeroSettingsService::class)->getDefaults();

        Setting::set(
            HeroSettingsService::SETTING_KEY,
            json_encode($defaults, JSON_UNESCAPED_UNICODE),
            HeroSettingsService::SETTING_GROUP
        );
    }

    public function down(): void
    {
        Setting::query()
            ->where('key', HeroSettingsService::SETTING_KEY)
            ->delete();
    }
};
