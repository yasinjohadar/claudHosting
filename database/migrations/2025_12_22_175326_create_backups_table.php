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
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('اسم النسخة');
            $table->enum('type', ['manual', 'scheduled', 'automatic'])->default('manual')->comment('نوع النسخ');
            $table->enum('backup_type', ['full', 'database', 'files', 'config'])->default('full')->comment('نوع المحتوى');
            $table->string('storage_driver')->comment('نوع التخزين (local, s3, google_drive, etc.)');
            $table->string('storage_path')->comment('مسار التخزين');
            $table->string('file_path')->comment('مسار الملف');
            $table->unsignedBigInteger('file_size')->default(0)->comment('حجم الملف بالبايت');
            $table->enum('compression_type', ['zip', 'gzip', 'tar'])->default('zip')->comment('نوع الضغط');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending')->comment('الحالة');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->integer('duration')->nullable()->comment('المدة بالثواني');
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable()->comment('بيانات إضافية');
            $table->integer('retention_days')->default(30)->comment('عدد أيام الاحتفاظ');
            $table->dateTime('expires_at')->nullable()->comment('تاريخ انتهاء الصلاحية');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('backup_schedule_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('type');
            $table->index('backup_type');
            $table->index('status');
            $table->index('storage_driver');
            $table->index('expires_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
