<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('client_domain_id')->nullable()->after('whm_account_id')->constrained('client_domains')->nullOnDelete();
            $table->index('client_domain_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['client_domain_id']);
            $table->dropColumn('client_domain_id');
        });
    }
};
