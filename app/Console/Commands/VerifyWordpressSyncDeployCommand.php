<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class VerifyWordpressSyncDeployCommand extends Command
{
    protected $signature = 'coolify:verify-wordpress-sync-deploy';

    protected $description = 'التحقق من أن ميزات مزامنة مواقع WordPress منشورة على هذا السيرفر';

    public function handle(): int
    {
        $routes = [
            'admin.coolify.wordpress-sites.sync-cloudflare',
            'admin.coolify.wordpress-sites.apply-coolify-domain',
        ];

        $this->info('فحص المسارات:');
        $ok = true;
        foreach ($routes as $name) {
            $route = Route::getRoutes()->getByName($name);
            if ($route === null) {
                $this->error('  ✗ '.$name.' — غير موجود (الكود القديم؟)');
                $ok = false;
            } else {
                $this->line('  ✓ '.$name);
            }
        }

        $files = [
            'app/Services/Coolify/WordpressCloudflareService.php',
            'app/Services/CoolifyApiService.php',
            'resources/views/admin/coolify/wordpress-sites/partials/sync-cloudflare-form.blade.php',
            'resources/views/admin/coolify/wordpress-sites/partials/apply-coolify-domain-form.blade.php',
        ];

        $this->newLine();
        $this->info('فحص الملفات:');
        foreach ($files as $file) {
            $path = base_path($file);
            if (! is_file($path)) {
                $this->error('  ✗ '.$file);
                $ok = false;
            } else {
                $this->line('  ✓ '.$file);
            }
        }

        if (method_exists(\App\Services\CoolifyApiService::class, 'buildServiceUrlsForService')) {
            $this->line('  ✓ buildServiceUrlsForService (إصلاح النطاق على Coolify)');
        } else {
            $this->error('  ✗ buildServiceUrlsForService غير موجود — حدّث الكود');
            $ok = false;
        }

        if ($ok) {
            $this->newLine();
            $this->info('كل شيء جاهز. مثال مزامنة: php artisan coolify:sync-wordpress-site site1 --all');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('انشر آخر نسخة من المشروع (git pull + composer + artisan) ثم أعد الفحص.');

        return self::FAILURE;
    }
}
