<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        if ($this->foreignKeyExists('tickets', 'tickets_whmcs_client_id_foreign')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropForeign(['whmcs_client_id']);
            });
        }

        if (! Schema::hasColumn('tickets', 'customer_id')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->after('id')->constrained('customers')->nullOnDelete();
            });
        }

        DB::table('tickets')
            ->whereNotNull('whmcs_client_id')
            ->orderBy('id')
            ->chunkById(100, function ($tickets) {
                foreach ($tickets as $ticket) {
                    if (! empty($ticket->customer_id)) {
                        continue;
                    }

                    $customerId = DB::table('customers')
                        ->where('whmcs_id', $ticket->whmcs_client_id)
                        ->value('id');

                    if ($customerId) {
                        DB::table('tickets')
                            ->where('id', $ticket->id)
                            ->update(['customer_id' => $customerId]);
                    }
                }
            });

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'whmcs_id')) {
                try {
                    $table->dropUnique(['whmcs_id']);
                } catch (\Throwable) {
                    // قد يكون مُزالاً مسبقاً
                }
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'whmcs_id')) {
                $table->unsignedBigInteger('whmcs_id')->nullable()->change();
            }
            if (Schema::hasColumn('tickets', 'whmcs_client_id')) {
                $table->unsignedBigInteger('whmcs_client_id')->nullable()->change();
            }
        });

        if (Schema::hasTable('ticket_replies') && Schema::hasColumn('ticket_replies', 'whmcs_id')) {
            Schema::table('ticket_replies', function (Blueprint $table) {
                try {
                    $table->dropUnique(['whmcs_id']);
                } catch (\Throwable) {
                }
            });

            Schema::table('ticket_replies', function (Blueprint $table) {
                $table->unsignedBigInteger('whmcs_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tickets') || ! Schema::hasColumn('tickets', 'customer_id')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() === 'sqlite') {
            return false;
        }

        $database = $connection->getDatabaseName();

        return (bool) DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
