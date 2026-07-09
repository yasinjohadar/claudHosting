<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evolution_instances', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('is_default');
            $table->string('label')->nullable()->after('instance_name');
            $table->string('evolution_base_url')->nullable()->after('metadata');
            $table->text('evolution_api_key')->nullable()->after('evolution_base_url');
        });
    }

    public function down(): void
    {
        Schema::table('evolution_instances', function (Blueprint $table) {
            $table->dropColumn(['is_manual', 'label', 'evolution_base_url', 'evolution_api_key']);
        });
    }
};
