<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('whm_accounts', 'email')) {
            Schema::table('whm_accounts', function (Blueprint $table) {
                $table->string('email', 255)->nullable()->after('domain');
                $table->index('email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('whm_accounts', 'email')) {
            Schema::table('whm_accounts', function (Blueprint $table) {
                $table->dropIndex(['email']);
                $table->dropColumn('email');
            });
        }
    }
};
