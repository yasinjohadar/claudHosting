<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'whm_provision')) {
            $after = Schema::hasColumn('products', 'coolify_provision') ? 'coolify_provision' : 'hidden';
            Schema::table('products', function (Blueprint $table) use ($after) {
                $table->json('whm_provision')->nullable()->after($after);
            });
        }

        if (! Schema::hasColumn('package_order_requests', 'whm_account_id')) {
            Schema::table('package_order_requests', function (Blueprint $table) {
                $after = Schema::hasColumn('package_order_requests', 'coolify_wordpress_site_id')
                    ? 'coolify_wordpress_site_id'
                    : 'provision_status';
                $table->foreignId('whm_account_id')->nullable()->after($after)->constrained('whm_accounts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('package_order_requests', 'whm_account_id')) {
            Schema::table('package_order_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('whm_account_id');
            });
        }

        if (Schema::hasColumn('products', 'whm_provision')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('whm_provision');
            });
        }
    }
};
