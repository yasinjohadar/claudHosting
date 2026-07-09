<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_broadcasts', 'campaign_type')) {
                $table->string('campaign_type', 50)->default('standard')->after('send_type');
            }
            if (! Schema::hasColumn('whatsapp_broadcasts', 'whatsapp_group_jid')) {
                $table->string('whatsapp_group_jid', 255)->nullable()->after('group_id');
            }
            if (! Schema::hasColumn('whatsapp_broadcasts', 'whatsapp_group_name')) {
                $table->string('whatsapp_group_name', 255)->nullable()->after('whatsapp_group_jid');
            }
            if (! Schema::hasColumn('whatsapp_broadcasts', 'meta')) {
                $table->json('meta')->nullable()->after('whatsapp_group_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
            foreach (['campaign_type', 'whatsapp_group_jid', 'whatsapp_group_name', 'meta'] as $col) {
                if (Schema::hasColumn('whatsapp_broadcasts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
