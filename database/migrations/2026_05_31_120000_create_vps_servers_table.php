<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vps_servers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('provider', 32);
            $table->string('external_id', 64);
            $table->string('name');
            $table->string('ip')->nullable();
            $table->string('region')->nullable();
            $table->string('status', 32)->default('unknown');
            $table->uuid('coolify_server_uuid')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_id']);
            $table->index('status');
        });

        Schema::create('vps_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vps_server_id')->constrained('vps_servers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 32);
            $table->boolean('success')->default(false);
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['vps_server_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vps_action_logs');
        Schema::dropIfExists('vps_servers');
    }
};
