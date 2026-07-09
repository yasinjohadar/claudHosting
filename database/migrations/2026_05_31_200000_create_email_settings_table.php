<?php

use App\Models\EmailSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mail_mailer')->default('smtp');
            $table->string('mail_host')->nullable();
            $table->integer('mail_port')->default(587);
            $table->string('mail_username')->nullable();
            $table->text('mail_password')->nullable();
            $table->string('mail_encryption')->default('tls');
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('provider')->default('custom');
            $table->json('test_results')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
        });

        $this->migrateLegacyMailSettings();
    }

    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }

    private function migrateLegacyMailSettings(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $rows = DB::table('system_settings')
            ->where('group', 'mail')
            ->pluck('value', 'key');

        if ($rows->isEmpty() || empty($rows['host'])) {
            return;
        }

        $mailEnabled = filter_var($rows['mail_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $encryption = (string) ($rows['encryption'] ?? 'tls');
        if ($encryption === '') {
            $encryption = 'none';
        }

        EmailSetting::query()->create([
            'mail_mailer' => 'smtp',
            'mail_host' => (string) $rows['host'],
            'mail_port' => (int) ($rows['port'] ?? 587),
            'mail_username' => (string) ($rows['username'] ?? ''),
            'mail_password' => (string) ($rows['password'] ?? ''),
            'mail_encryption' => $encryption,
            'mail_from_address' => (string) ($rows['from_address'] ?? config('mail.from.address')),
            'mail_from_name' => (string) ($rows['from_name'] ?? config('app.name')),
            'is_active' => $mailEnabled,
            'provider' => 'custom',
        ]);
    }
};
