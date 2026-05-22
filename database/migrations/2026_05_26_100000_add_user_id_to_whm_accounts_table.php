<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whm_accounts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->index('user_id');
        });

        if (Schema::hasTable('customers')) {
            DB::statement('
                UPDATE whm_accounts wa
                INNER JOIN customers c ON wa.customer_id = c.id
                SET wa.user_id = c.user_id
                WHERE c.user_id IS NOT NULL AND wa.user_id IS NULL
            ');
        }
    }

    public function down(): void
    {
        Schema::table('whm_accounts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
