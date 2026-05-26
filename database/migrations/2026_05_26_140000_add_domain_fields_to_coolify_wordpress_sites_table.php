<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coolify_wordpress_sites', function (Blueprint $table) {
            $table->string('domain_type', 32)->default('platform_subdomain')->after('slug');
            $table->string('primary_hostname')->nullable()->after('domain_type');
            $table->string('custom_domain_apex')->nullable()->after('primary_hostname');
        });
    }

    public function down(): void
    {
        Schema::table('coolify_wordpress_sites', function (Blueprint $table) {
            $table->dropColumn(['domain_type', 'primary_hostname', 'custom_domain_apex']);
        });
    }
};
