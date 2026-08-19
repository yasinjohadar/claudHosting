<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields the admin UI needs, plus the flag that protects templates the code depends on.
 *
 * Without `is_system`, deleting a row would silently break the flow that looks it up by slug:
 * payment notifications or OTP messages would just stop, with no error anywhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_message_templates', function (Blueprint $table) {
            $table->string('category', 40)->default('general')->after('type');
            $table->string('description')->nullable()->after('name');
            $table->boolean('is_system')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_message_templates', function (Blueprint $table) {
            $table->dropColumn(['category', 'description', 'is_system']);
        });
    }
};
