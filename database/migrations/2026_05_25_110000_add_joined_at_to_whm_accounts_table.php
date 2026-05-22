<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('whm_accounts', 'joined_at')) {
            Schema::table('whm_accounts', function (Blueprint $table) {
                $table->timestamp('joined_at')->nullable()->after('email');
                $table->index('joined_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('whm_accounts', 'joined_at')) {
            Schema::table('whm_accounts', function (Blueprint $table) {
                $table->dropIndex(['joined_at']);
                $table->dropColumn('joined_at');
            });
        }
    }
};
