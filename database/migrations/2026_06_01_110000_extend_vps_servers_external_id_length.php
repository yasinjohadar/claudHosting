<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vps_servers', function (Blueprint $table) {
            $table->string('external_id', 191)->change();
        });
    }

    public function down(): void
    {
        Schema::table('vps_servers', function (Blueprint $table) {
            $table->string('external_id', 64)->change();
        });
    }
};
