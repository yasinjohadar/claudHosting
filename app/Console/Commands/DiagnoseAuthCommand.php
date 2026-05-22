<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

class DiagnoseAuthCommand extends Command
{
    protected $signature = 'auth:diagnose {email? : البريد للتحقق من أدواره}';

    protected $description = 'تشخيص مشاكل تسجيل الدخول والتوجيه (مفيد على السيرفر)';

    public function handle(): int
    {
        $this->info('=== تشخيص المصادقة ===');
        $this->line('APP_URL: '.config('app.url'));
        $this->line('APP_ENV: '.config('app.env'));
        $this->line('SESSION_DRIVER: '.config('session.driver'));

        $routes = [
            'login' => Route::has('login'),
            'home' => Route::has('home'),
            'admin.dashboard' => Route::has('admin.dashboard'),
            'client.dashboard' => Route::has('client.dashboard'),
            'dashboard (legacy)' => Route::has('dashboard'),
        ];

        $this->newLine();
        $this->info('المسارات:');
        foreach ($routes as $name => $exists) {
            $this->line(sprintf('  %-22s %s', $name, $exists ? '✓' : '✗'));
        }

        $this->newLine();
        $this->info('الأدوار في قاعدة البيانات:');
        $roles = Role::pluck('name')->all();
        $this->line($roles ? '  '.implode(', ', $roles) : '  (لا توجد أدوار — شغّل db:seed أو أنشئ دور admin)');

        $email = $this->argument('email');
        if ($email) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                $this->error("لا يوجد مستخدم بالبريد: {$email}");

                return self::FAILURE;
            }

            $this->newLine();
            $this->info("المستخدم: {$user->name} ({$user->email})");
            $this->line('  is_active: '.($user->is_active ? 'yes' : 'no'));
            $this->line('  status: '.$user->status);
            $this->line('  roles: '.implode(', ', $user->getRoleNames()->all()) ?: '(none)');
            $this->line('  isAdminPanelUser: '.($user->isAdminPanelUser() ? 'YES' : 'NO'));

            if (! $user->isAdminPanelUser()) {
                $this->warn('  → هذا الحساب لن يدخل /admin. عيّن دور admin: php artisan auth:ensure-admin '.$email);
            }
        } else {
            $this->newLine();
            $this->comment('لتفاصيل مستخدم: php artisan auth:diagnose admin@example.com');
        }

        $this->newLine();
        $this->comment('على السيرفر بعد كل نشر: php artisan optimize:clear && php artisan permission:cache-reset');

        return self::SUCCESS;
    }
}
