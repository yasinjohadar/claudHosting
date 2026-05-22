<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['whmcs_client_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('id');
            $table->string('invoice_number', 50)->nullable()->unique()->after('customer_id');
        });

        DB::table('invoices')
            ->whereNotNull('whmcs_client_id')
            ->orderBy('id')
            ->chunkById(100, function ($invoices) {
                foreach ($invoices as $invoice) {
                    $customerId = DB::table('customers')
                        ->where('whmcs_id', $invoice->whmcs_client_id)
                        ->value('id');

                    if ($customerId) {
                        DB::table('invoices')
                            ->where('id', $invoice->id)
                            ->update(['customer_id' => $customerId]);
                    }
                }
            });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['whmcs_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('whmcs_id')->nullable()->change();
            $table->unsignedBigInteger('whmcs_client_id')->nullable()->change();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->index('customer_id');
        });

        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropUnique(['whmcs_invoice_item_id']);
            });

            Schema::table('invoice_items', function (Blueprint $table) {
                $table->unsignedBigInteger('whmcs_invoice_item_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if ($this->foreignKeyExists('payments', 'payments_whmcs_client_id_foreign')) {
                    $table->dropForeign(['whmcs_client_id']);
                }
            });

            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('whmcs_invoice_id')->nullable()->change();
                $table->unsignedBigInteger('whmcs_client_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'invoice_number']);
            $table->unsignedBigInteger('whmcs_id')->nullable(false)->change();
            $table->unsignedBigInteger('whmcs_client_id')->nullable(false)->change();
            $table->foreign('whmcs_client_id')->references('whmcs_id')->on('customers')->onDelete('cascade');
        });
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        if ($connection->getDriverName() === 'sqlite') {
            return false;
        }

        return (bool) DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
