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
        Schema::create('whatsapp_message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم القالب (عرض في القوائم)
            $table->string('slug')->nullable()->unique(); // للاستخدام برمجياً (مثل welcome_group)
            $table->text('body'); // نص الرسالة (لنوع text)
            $table->string('type', 20)->default('text'); // text | template (Meta)
            $table->string('language', 10)->default('ar');
            $table->string('meta_template_name')->nullable(); // اسم القالب في Meta (لنوع template)
            $table->json('variables')->nullable(); // قائمة المتغيرات المتاحة مثل ["student_name","course_name"]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_templates');
    }
};
