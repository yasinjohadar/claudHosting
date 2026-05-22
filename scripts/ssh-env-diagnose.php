<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$key = storage_path('app/coolify-keys/server.pem');
$host = '194.163.144.165';
$sshExe = 'C:\\Windows\\System32\\OpenSSH\\ssh.exe';

echo 'SAPI: '.PHP_SAPI.PHP_EOL;
echo 'User: '.(function_exists('get_current_user') ? get_current_user() : '?').PHP_EOL;
echo 'PATH: '.(getenv('PATH') ?: '(empty)').PHP_EOL;
echo 'ssh exists: '.(is_file($sshExe) ? 'yes' : 'no').PHP_EOL;
echo 'key readable: '.(is_readable($key) ? 'yes' : 'no').PHP_EOL;

$proc = new Symfony\Component\Process\Process([
    $sshExe,
    '-i', $key,
    '-p', '22',
    '-o', 'BatchMode=yes',
    '-o', 'ConnectTimeout=15',
    '-o', 'IdentitiesOnly=yes',
    '-o', 'StrictHostKeyChecking=accept-new',
    'root@'.$host,
    'echo coolify-ssh-ok',
]);
$proc->setTimeout(30);
$proc->run();
echo 'exit: '.($proc->getExitCode() ?? 'null').PHP_EOL;
echo 'out: '.trim($proc->getOutput()).PHP_EOL;
echo 'err: '.trim($proc->getErrorOutput()).PHP_EOL;

$ssh = app(\App\Services\Coolify\CoolifySshExecutor::class);
echo 'executor test: '.json_encode($ssh->testConnection($host), JSON_UNESCAPED_UNICODE).PHP_EOL;
