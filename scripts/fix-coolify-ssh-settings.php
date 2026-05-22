<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$storageKey = storage_path('app/coolify-keys/server.pem');
if (! is_file($storageKey)) {
    fwrite(STDERR, "Missing {$storageKey}. Run copy from coolify-key-fixed.pem first.\n");
    exit(1);
}

app(\App\Services\Coolify\CoolifySettingsService::class)->updateSettings([
    'ssh_host_fallback' => '194.163.144.165',
    'ssh_private_key_path' => $storageKey,
    'ssh_private_key' => '',
    'ssh_port' => 22,
]);

app(\App\Services\Coolify\CoolifySettingsService::class)->clearCache();

$ssh = app(\App\Services\Coolify\CoolifySshExecutor::class);
$result = $ssh->testConnection('194.163.144.165');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
exit(($result['success'] ?? false) ? 0 : 1);
