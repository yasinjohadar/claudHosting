<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$settings = app(\App\Services\Coolify\CoolifySettingsService::class);
$ssh = app(\App\Services\Coolify\CoolifySshExecutor::class);

$config = $settings->getSshConfig();
echo "DB path: ".($config['ssh_private_key_path'] ?? '(empty)').PHP_EOL;
echo "DB host: ".$settings->getSshHostFallback().PHP_EOL;
echo "server.pem exists: ".(is_file($ssh->defaultStorageKeyPath()) ? 'yes' : 'no').PHP_EOL;
echo "server.pem readable: ".(is_readable($ssh->defaultStorageKeyPath()) ? 'yes' : 'no').PHP_EOL;
echo "keygen server.pem: ".($ssh->keyFilePassesSshKeygen($ssh->defaultStorageKeyPath()) ? 'ok' : 'fail').PHP_EOL;

// Simulate UI test with stale C:\temp path (like user's form)
$stale = 'C:\\temp\\coolify-key.pem';
$resolved = $ssh->resolveKeyPathForTest($stale);
if ($resolved === '' && $ssh->keyFilePassesSshKeygen($ssh->defaultStorageKeyPath())) {
    $resolved = $ssh->defaultStorageKeyPath();
}
echo "Resolved from C:\\temp: ".$resolved.PHP_EOL;

$r1 = $ssh->testConnection('194.163.144.165');
echo "test saved settings: ".json_encode($r1, JSON_UNESCAPED_UNICODE).PHP_EOL;

$r2 = $ssh->testConnection('194.163.144.165', null, $resolved !== '' ? $resolved : null);
echo "test with override: ".json_encode($r2, JSON_UNESCAPED_UNICODE).PHP_EOL;

$r3 = $ssh->testConnection('194.163.144.165', null, $stale);
echo "test raw C:\\temp: ".json_encode($r3, JSON_UNESCAPED_UNICODE).PHP_EOL;
