<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('coolify_provision')->nullable()->after('hidden');
        });

        Schema::table('package_order_requests', function (Blueprint $table) {
            $table->string('provision_status', 32)->nullable()->after('status');
            $table->foreignId('coolify_wordpress_site_id')->nullable()->after('provision_status')
                ->constrained('coolify_wordpress_sites')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('package_order_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coolify_wordpress_site_id');
            $table->dropColumn('provision_status');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('coolify_provision');
        });
    }
};
