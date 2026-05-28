<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ticket_replies')) {
            return;
        }

        Schema::table('ticket_replies', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_replies', 'whmcs_ticket_id')) {
                $table->unsignedBigInteger('whmcs_ticket_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // لا حاجة للتراجع
    }
};
