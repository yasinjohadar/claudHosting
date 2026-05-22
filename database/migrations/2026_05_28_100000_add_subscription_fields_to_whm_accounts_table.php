<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whm_accounts', function (Blueprint $table) {
            $table->timestamp('subscription_ends_at')->nullable()->after('joined_at');
            $table->timestamp('last_renewed_at')->nullable()->after('subscription_ends_at');
            $table->index('subscription_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('whm_accounts', function (Blueprint $table) {
            $table->dropIndex(['subscription_ends_at']);
            $table->dropColumn(['subscription_ends_at', 'last_renewed_at']);
        });
    }
};
