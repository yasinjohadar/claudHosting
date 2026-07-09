<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_broadcast_recipients', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_broadcast_recipients', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('user_id')->constrained('customers')->cascadeOnDelete();
            }
        });

        Schema::table('whatsapp_broadcast_recipients', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_broadcast_recipients', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->foreignId('user_id')->nullable()->change();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcast_recipients', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_broadcast_recipients', 'customer_id')) {
                $table->dropConstrainedForeignId('customer_id');
            }
        });
    }
};
