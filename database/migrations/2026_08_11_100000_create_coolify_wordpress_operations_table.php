<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coolify_wordpress_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coolify_wordpress_site_id')
                ->constrained('coolify_wordpress_sites')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('job_id')->nullable()->index();
            $table->string('action');
            $table->string('action_label')->nullable();
            $table->json('params')->nullable();
            $table->string('status')->default('queued'); // queued|running|completed|failed
            $table->boolean('success')->nullable();
            $table->text('message')->nullable();
            $table->longText('output')->nullable();
            $table->string('result_file_path')->nullable();
            $table->unsignedBigInteger('result_file_size')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['coolify_wordpress_site_id', 'created_at'], 'cwo_site_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coolify_wordpress_operations');
    }
};
