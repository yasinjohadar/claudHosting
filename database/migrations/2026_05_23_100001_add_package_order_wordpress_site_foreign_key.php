<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coolify_wordpress_sites')) {
            return;
        }

        if (! Schema::hasColumn('package_order_requests', 'coolify_wordpress_site_id')) {
            return;
        }

        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        $exists = $connection->selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$database, 'package_order_requests', 'package_order_requests_coolify_wordpress_site_id_foreign']
        );

        if ($exists !== null) {
            return;
        }

        Schema::table('package_order_requests', function (Blueprint $table) {
            $table->foreign('coolify_wordpress_site_id')
                ->references('id')
                ->on('coolify_wordpress_sites')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('package_order_requests', 'coolify_wordpress_site_id')) {
            return;
        }

        Schema::table('package_order_requests', function (Blueprint $table) {
            $table->dropForeign(['coolify_wordpress_site_id']);
        });
    }
};
