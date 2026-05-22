<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'coolify_provision')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('coolify_provision')->nullable()->after('hidden');
            });
        }

        if (! Schema::hasColumn('package_order_requests', 'provision_status')) {
            Schema::table('package_order_requests', function (Blueprint $table) {
                $table->string('provision_status', 32)->nullable()->after('status');
            });
        }

        if (! Schema::hasColumn('package_order_requests', 'coolify_wordpress_site_id')) {
            Schema::table('package_order_requests', function (Blueprint $table) {
                $after = Schema::hasColumn('package_order_requests', 'provision_status')
                    ? 'provision_status'
                    : 'status';
                $table->unsignedBigInteger('coolify_wordpress_site_id')->nullable()->after($after);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('package_order_requests', 'coolify_wordpress_site_id')) {
            Schema::table('package_order_requests', function (Blueprint $table) {
                if ($this->foreignKeyExists('package_order_requests', 'package_order_requests_coolify_wordpress_site_id_foreign')) {
                    $table->dropForeign(['coolify_wordpress_site_id']);
                }
                $table->dropColumn('coolify_wordpress_site_id');
            });
        }

        if (Schema::hasColumn('package_order_requests', 'provision_status')) {
            Schema::table('package_order_requests', function (Blueprint $table) {
                $table->dropColumn('provision_status');
            });
        }

        if (Schema::hasColumn('products', 'coolify_provision')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('coolify_provision');
            });
        }
    }

    protected function foreignKeyExists(string $table, string $foreignName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$database, $table, $foreignName, 'FOREIGN KEY']
        );

        return $result !== null;
    }
};
