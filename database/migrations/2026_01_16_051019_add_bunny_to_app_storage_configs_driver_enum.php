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
        // في MySQL، لا يمكن تعديل enum مباشرة، يجب حذف العمود وإعادة إنشائه
        Schema::table('app_storage_configs', function (Blueprint $table) {
            // حذف العمود القديم
            $table->dropColumn('driver');
        });

        Schema::table('app_storage_configs', function (Blueprint $table) {
            // إعادة إنشاء العمود مع إضافة 'bunny'
            $table->enum('driver', [
                'local', 
                's3', 
                'google_drive', 
                'dropbox', 
                'azure', 
                'ftp', 
                'sftp', 
                'digitalocean', 
                'wasabi', 
                'backblaze', 
                'cloudflare_r2',
                'bunny'  // إضافة bunny
            ])->comment('نوع التخزين')->after('name');
            
            // إعادة إضافة الفهرس
            $table->index('driver');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إعادة العمود إلى حالته الأصلية بدون 'bunny'
        Schema::table('app_storage_configs', function (Blueprint $table) {
            $table->dropColumn('driver');
        });

        Schema::table('app_storage_configs', function (Blueprint $table) {
            $table->enum('driver', [
                'local', 
                's3', 
                'google_drive', 
                'dropbox', 
                'azure', 
                'ftp', 
                'sftp', 
                'digitalocean', 
                'wasabi', 
                'backblaze', 
                'cloudflare_r2'
            ])->comment('نوع التخزين')->after('name');
            
            $table->index('driver');
        });
    }
};
