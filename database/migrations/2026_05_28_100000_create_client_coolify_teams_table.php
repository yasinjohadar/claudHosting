<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_coolify_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('coolify_team_id');
            $table->string('team_name')->nullable();
            $table->text('api_token')->nullable();
            $table->timestamp('token_configured_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('coolify_team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_coolify_teams');
    }
};
