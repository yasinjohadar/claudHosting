<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_dns_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('whm_account_id')->nullable()->constrained('whm_accounts')->nullOnDelete();
            $table->string('domain', 253)->index();
            $table->string('zone_id', 64)->nullable();
            $table->string('zone_name', 253)->nullable();
            // web | client | command — an admin button, a client button and a CLI run
            // must stay distinguishable in the audit trail.
            $table->string('source', 32)->default('web');
            // applied | partial | failed | blocked | dry_run
            $table->string('outcome', 32)->index();
            $table->unsignedSmallInteger('created_count')->default(0);
            $table->unsignedSmallInteger('updated_count')->default(0);
            $table->unsignedSmallInteger('failed_count')->default(0);
            $table->text('message')->nullable();
            // Per-record before/after/result, so a mixed state stays reconstructable.
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_dns_sync_logs');
    }
};
