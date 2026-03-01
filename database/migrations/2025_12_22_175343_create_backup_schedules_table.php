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
        Schema::create('backup_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('اسم الجدولة');
            $table->enum('backup_type', ['full', 'database', 'files', 'config'])->default('full');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'custom'])->default('daily')->comment('التكرار');
            $table->time('time')->comment('وقت التنفيذ');
            $table->json('days_of_week')->nullable()->comment('أيام الأسبوع [1,3,5] للـ weekly');
            $table->integer('day_of_month')->nullable()->comment('يوم الشهر للـ monthly');
            $table->json('storage_drivers')->comment('قائمة أماكن التخزين [\'local\', \'s3\']');
            $table->json('compression_types')->comment('أنواع الضغط [\'zip\', \'gzip\']');
            $table->integer('retention_days')->default(30)->comment('أيام الاحتفاظ');
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_run_at')->nullable();
            $table->dateTime('next_run_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('next_run_at');
            $table->index('frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_schedules');
    }
};
