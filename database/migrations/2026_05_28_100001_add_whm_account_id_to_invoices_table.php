<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('whm_account_id')->nullable()->after('customer_id')->constrained('whm_accounts')->nullOnDelete();
            $table->index('whm_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['whm_account_id']);
            $table->dropColumn('whm_account_id');
        });
    }
};
