<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whm_wordpress_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whm_account_id')->constrained('whm_accounts')->cascadeOnDelete();
            $table->string('source', 32); // softaculous|wp_toolkit|manual
            $table->string('external_id')->nullable();
            $table->string('domain')->nullable();
            $table->string('path')->nullable();
            $table->string('url')->nullable();
            $table->string('wp_version', 32)->nullable();
            $table->string('title')->nullable();
            $table->string('status', 32)->default('active'); // active|missing|unknown
            $table->json('metadata')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['whm_account_id', 'source', 'external_id'], 'whm_wp_acct_source_ext_unique');
            $table->index(['whm_account_id', 'path']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whm_wordpress_sites');
    }
};
