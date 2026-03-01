<?php

namespace App\Console\Commands;

use App\Models\BackupStorageConfig;
use App\Services\Backup\BackupStorageService;
use Illuminate\Console\Command;

class TestBackupStorageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:test-storage {config? : ID of storage config to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'اختبار اتصالات التخزين';

    /**
     * Execute the console command.
     */
    public function handle(BackupStorageService $storageService): int
    {
        $configId = $this->argument('config');

        if ($configId) {
            $config = BackupStorageConfig::find($configId);
            if (!$config) {
                $this->error('إعدادات التخزين غير موجودة.');
                return Command::FAILURE;
            }

            $this->testConfig($config, $storageService);
        } else {
            $configs = BackupStorageConfig::where('is_active', true)->get();
            
            if ($configs->isEmpty()) {
                $this->info('لا توجد إعدادات تخزين نشطة.');
                return Command::SUCCESS;
            }

            foreach ($configs as $config) {
                $this->testConfig($config, $storageService);
            }
        }

        return Command::SUCCESS;
    }

    private function testConfig(BackupStorageConfig $config, BackupStorageService $storageService): void
    {
        $this->info("اختبار: {$config->name} ({$config->driver})...");

        $configArray = $config->getDecryptedConfig();
        $result = $storageService->testStorageConnection($config->driver, $configArray);

        if ($result) {
            $this->info("✓ نجح الاتصال بـ {$config->name}");
        } else {
            $this->error("✗ فشل الاتصال بـ {$config->name}");
        }
    }
}
