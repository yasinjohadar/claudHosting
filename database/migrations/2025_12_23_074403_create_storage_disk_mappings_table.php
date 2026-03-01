<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('storage_disk_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('disk_name')->unique()->comment('اسم الـ disk');
            $table->string('label')->comment('التسمية');
            $table->foreignId('primary_storage_id')->constrained('app_storage_configs')->onDelete('cascade');
            $table->json('fallback_storage_ids')->nullable()->comment('أماكن التخزين الاحتياطية');
            $table->json('file_types')->nullable()->comment('أنواع الملفات المدعومة');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('disk_name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_disk_mappings');
    }
};
