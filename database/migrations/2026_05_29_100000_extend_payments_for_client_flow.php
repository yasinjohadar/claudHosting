<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('invoice_id')->constrained('customers')->nullOnDelete();
            $table->text('notes')->nullable()->after('status');
            $table->foreignId('recorded_by_user_id')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->string('proof_path')->nullable()->after('recorded_by_user_id');
            $table->string('initiated_by', 20)->default('admin')->after('proof_path');

            $table->index('customer_id');
            $table->index('initiated_by');
        });

        if (Schema::hasTable('invoices')) {
            DB::statement('
                UPDATE payments p
                INNER JOIN invoices i ON i.id = p.invoice_id
                SET p.customer_id = i.customer_id
                WHERE p.customer_id IS NULL AND i.customer_id IS NOT NULL
            ');
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['recorded_by_user_id']);
            $table->dropColumn(['customer_id', 'notes', 'recorded_by_user_id', 'proof_path', 'initiated_by']);
        });
    }
};
