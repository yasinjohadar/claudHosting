<?php

namespace App\Console\Commands;

use App\Services\Coolify\CoolifySettingsService;
use App\Services\Coolify\CoolifySshExecutor;
use Illuminate\Console\Command;

class CoolifyFixSshCommand extends Command
{
    protected $signature = 'coolify:fix-ssh {--host=194.163.144.165 : SSH host to test}';

    protected $description = 'ضبط مسار مفتاح SSH إلى storage/app/coolify-keys/server.pem واختبار الاتصال';

    public function handle(CoolifySettingsService $settings, CoolifySshExecutor $ssh): int
    {
        $keyPath = $ssh->defaultStorageKeyPath();
        if (! is_file($keyPath)) {
            $this->error('الملف غير موجود: '.$keyPath);
            $this->line('انسخ المفتاح الصالح (بعد إصلاح base64) إلى هذا المسار ثم أعد التشغيل.');

            return self::FAILURE;
        }

        if (! $ssh->keyFilePassesSshKeygen($keyPath)) {
            $this->error('المفتاح غير صالح (ssh-keygen -y فشل): '.$keyPath);

            return self::FAILURE;
        }

        $host = (string) $this->option('host');
        $settings->updateSettings([
            'ssh_host_fallback' => $host,
            'ssh_private_key_path' => $keyPath,
            'ssh_private_key' => '',
            'ssh_port' => 22,
        ]);
        $settings->clearCache();

        $this->info('تم الحفظ: '.$keyPath);

        $result = $ssh->testConnection($host);
        if ($result['success'] ?? false) {
            $this->info($result['message'] ?? 'SSH OK');

            return self::SUCCESS;
        }

        $this->error($result['message'] ?? 'فشل SSH');

        return self::FAILURE;
    }
}
