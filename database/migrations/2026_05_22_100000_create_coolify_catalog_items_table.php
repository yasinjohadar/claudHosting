<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coolify_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('category', 32);
            $table->string('coolify_key')->nullable();
            $table->string('name_ar');
            $table->text('description_ar')->nullable();
            $table->string('icon', 64)->default('fe-box');
            $table->boolean('enabled')->default(true);
            $table->boolean('featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->json('install_steps')->nullable();
            $table->json('requirements')->nullable();
            $table->string('docs_url')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->string('install_mode', 32)->nullable();
            $table->string('custom_install_url')->nullable();
            $table->boolean('available_on_coolify')->nullable();
            $table->boolean('from_config')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coolify_catalog_items');
    }
};
