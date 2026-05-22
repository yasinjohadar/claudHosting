<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coolify_project_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('scope', 32);
            $table->string('project_uuid')->nullable()->index();
            $table->string('project_name')->nullable();
            $table->string('name');
            $table->string('status', 32)->default('pending')->index();
            $table->json('options')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('coolify_project_snapshot_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('coolify_project_snapshots')->cascadeOnDelete();
            $table->string('resource_type', 32);
            $table->string('resource_uuid');
            $table->string('resource_name')->nullable();
            $table->string('project_uuid')->nullable();
            $table->string('server_uuid')->nullable();
            $table->string('server_host')->nullable();
            $table->string('strategy', 32);
            $table->string('status', 32)->default('pending')->index();
            $table->string('backup_path')->nullable();
            $table->string('coolify_backup_config_uuid')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['snapshot_id', 'resource_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coolify_project_snapshot_items');
        Schema::dropIfExists('coolify_project_snapshots');
    }
};
