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
        Schema::create('app_storage_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('اسم الإعداد');
            $table->enum('driver', ['local', 's3', 'google_drive', 'dropbox', 'azure', 'ftp', 'sftp', 'digitalocean', 'wasabi', 'backblaze', 'cloudflare_r2'])->comment('نوع التخزين');
            $table->text('config')->comment('الإعدادات المشفرة');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0)->comment('الأولوية');
            $table->boolean('redundancy')->default(false)->comment('تفعيل التخزين المتعدد');
            $table->json('pricing_config')->nullable()->comment('إعدادات التسعير');
            $table->decimal('monthly_budget', 10, 2)->nullable();
            $table->decimal('cost_alert_threshold', 10, 2)->nullable();
            $table->string('cdn_url')->nullable()->comment('رابط CDN');
            $table->json('file_types')->nullable()->comment('أنواع الملفات المدعومة');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('driver');
            $table->index('is_active');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_storage_configs');
    }
};
