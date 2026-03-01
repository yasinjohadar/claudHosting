<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable()->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique(); // رقم الهاتف
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true); // حالة التفعيل
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active'); // حالة المستخدم
            $table->timestamp('last_login_at')->nullable(); // آخر تسجيل دخول
            $table->ipAddress('last_login_ip')->nullable(); // عنوان IP
            $table->text('last_login_user_agent')->nullable(); // نوع الجهاز أو المتصفح
            $table->text('photo')->nullable(); // صورة المستخدم
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // أنشأه من؟
            $table->rememberToken();
            $table->timestamps();
        });




        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();

            // معرف المستخدم (يمكن أن يكون null للجلسات غير المرتبطة بمستخدم مسجل)
            $table->foreignId('user_id')->nullable()->index();

            // عنوان IP للجلسة
            $table->string('ip_address', 45)->nullable();

            // بيانات الـ User Agent (المتصفح والجهاز)
            $table->text('user_agent')->nullable();

            // نوع الجهاز (Desktop, Mobile, Tablet, ... )
            $table->string('device_type')->nullable();

            // اسم المتصفح (Chrome, Firefox, Safari, ...)
            $table->string('browser')->nullable();

            // نظام التشغيل (Windows, macOS, Android, iOS, ...)
            $table->string('os')->nullable();

            // الموقع التقريبي للمستخدم (مدينة، دولة، حسب الـ IP)
            $table->string('location')->nullable();

            // وقت تسجيل الدخول (بداية الجلسة)
            $table->timestamp('login_at')->nullable();

            // وقت تسجيل الخروج (نهاية الجلسة)
            $table->timestamp('logout_at')->nullable();

            // مدة الجلسة بالثواني (logout_at - login_at)
            $table->integer('session_duration')->nullable();

            // هل الجلسة نشطة حالياً (true: نشطة، false: منتهية)
            $table->boolean('is_current')->default(true);

            // عدد محاولات الدخول الفاشلة (لأغراض الأمان)
            $table->integer('failed_attempts')->default(0);

            // بيانات الجلسة (مشفرة أو مسلسلة)
            $table->longText('payload');

            // آخر نشاط (timestamp) لتتبع نشاط الجلسة
            $table->integer('last_activity')->index();

            // تاريخ الإنشاء والتحديث (إن أردت تخزينها)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
