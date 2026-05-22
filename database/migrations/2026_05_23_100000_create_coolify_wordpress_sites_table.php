<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coolify_wordpress_sites', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('display_name');
            $table->string('slug')->unique();
            $table->string('project_mode', 16)->default('new');
            $table->string('project_uuid')->nullable()->index();
            $table->string('project_name')->nullable();
            $table->string('service_uuid')->nullable()->index();
            $table->string('server_uuid')->nullable();
            $table->string('environment_name', 64)->default('production');
            $table->string('public_url')->nullable();
            $table->string('admin_url')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coolify_wordpress_sites');
    }
};
