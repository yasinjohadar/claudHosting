<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->nullable();
            $table->timestamps();
        });

        $defaults = [
            ['key' => 'contact_email', 'value' => 'info@cloudsofthosting.com', 'group' => 'contact'],
            ['key' => 'contact_phone', 'value' => '+963 XXX XXX XXX', 'group' => 'contact'],
            ['key' => 'contact_whatsapp', 'value' => '+963 XXX XXX XXX', 'group' => 'contact'],
            ['key' => 'contact_address', 'value' => 'سوريا', 'group' => 'contact'],
            ['key' => 'contact_work_hours', 'value' => 'السبت - الخميس: 9:00 ص - 6:00 م', 'group' => 'contact'],
            ['key' => 'social_facebook', 'value' => 'https://facebook.com', 'group' => 'social'],
            ['key' => 'social_youtube', 'value' => 'https://youtube.com', 'group' => 'social'],
            ['key' => 'social_instagram', 'value' => 'https://instagram.com', 'group' => 'social'],
            ['key' => 'social_linkedin', 'value' => 'https://linkedin.com', 'group' => 'social'],
            ['key' => 'social_github', 'value' => 'https://github.com', 'group' => 'social'],
            ['key' => 'social_telegram', 'value' => 'https://t.me', 'group' => 'social'],
            ['key' => 'site_name', 'value' => 'ClaudSoft', 'group' => 'general'],
            ['key' => 'footer_description', 'value' => 'مدرب ومطور برمجيات شغوف بالتعليم ونقل المعرفة. أقدم دورات تدريبية عملية في مختلف مجالات البرمجة وتطوير الويب والموبايل.', 'group' => 'general'],
            ['key' => 'copyright_text', 'value' => 'جميع الحقوق محفوظة © 2026 استضافة كلاودسوفت | صُنع بـ ❤️', 'group' => 'general'],
            ['key' => 'contact_form_action', 'value' => 'https://formspree.io/f/YOUR_FORM_ID', 'group' => 'contact_form'],
        ];

        foreach ($defaults as $row) {
            DB::table('settings')->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
