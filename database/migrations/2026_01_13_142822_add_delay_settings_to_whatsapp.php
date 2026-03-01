<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SystemSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add delay settings to system_settings table
        $defaults = [
            'delay_between_messages' => '3', // seconds between messages
            'delay_between_broadcasts' => '5', // seconds between broadcast batches
            'max_messages_per_minute' => '20', // max messages per minute
            'random_delay_enabled' => 'true', // enable random delay variation
            'min_delay' => '2', // minimum delay in seconds
            'max_delay' => '5', // maximum delay in seconds
        ];

        foreach ($defaults as $key => $value) {
            if (!SystemSetting::byKey($key)->ofGroup('whatsapp')->exists()) {
                SystemSetting::set($key, $value, 'string', 'whatsapp');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove delay settings
        $keys = [
            'delay_between_messages',
            'delay_between_broadcasts',
            'max_messages_per_minute',
            'random_delay_enabled',
            'min_delay',
            'max_delay',
        ];

        foreach ($keys as $key) {
            SystemSetting::byKey($key)->ofGroup('whatsapp')->delete();
        }
    }
};
