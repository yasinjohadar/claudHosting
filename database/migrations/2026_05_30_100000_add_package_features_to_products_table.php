<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'package_features')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('package_features')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'package_features')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('package_features');
            });
        }
    }
};
