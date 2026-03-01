<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE customers MODIFY whmcs_id BIGINT UNSIGNED NULL UNIQUE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE customers MODIFY whmcs_id BIGINT UNSIGNED NOT NULL UNIQUE');
    }
};
