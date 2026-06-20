<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coolify_backup_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 64);
            $table->string('subject_type', 64)->nullable();
            $table->string('subject_uuid', 64)->nullable();
            $table->string('resource_type', 32)->nullable();
            $table->string('resource_uuid', 64)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->default('completed');
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'subject_uuid']);
        });

        Schema::create('coolify_restore_drills', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('snapshot_id')->nullable()->constrained('coolify_project_snapshots')->nullOnDelete();
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('items_total')->default(0);
            $table->unsignedInteger('items_verified')->default(0);
            $table->unsignedInteger('items_failed')->default(0);
            $table->text('summary')->nullable();
            $table->json('results')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coolify_restore_drills');
        Schema::dropIfExists('coolify_backup_audit_logs');
    }
};
