<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = \App\Models\User::query()->first();
if (! $user) {
    fwrite(STDERR, "No user\n");
    exit(1);
}

$request = Illuminate\Http\Request::create(
    '/admin/coolify/settings/test-ssh',
    'POST',
    ['host' => '194.163.144.165', 'ssh_private_key' => ''],
    [],
    [],
    ['HTTP_ACCEPT' => 'application/json']
);
$request->setUserResolver(fn () => $user);

$response = $kernel->handle($request);
echo $response->getContent().PHP_EOL;
$kernel->terminate($request, $response);
