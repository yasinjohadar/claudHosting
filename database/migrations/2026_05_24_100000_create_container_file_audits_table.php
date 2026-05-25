<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('container_file_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coolify_wordpress_site_id')->constrained('coolify_wordpress_sites')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 32);
            $table->string('path', 512)->nullable();
            $table->boolean('success')->default(false);
            $table->string('ip', 45)->nullable();
            $table->text('detail')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('container_file_audits');
    }
};
