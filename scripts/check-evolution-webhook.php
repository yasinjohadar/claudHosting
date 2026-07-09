<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$evo = app(App\Services\WhatsApp\Evolution\EvolutionService::class);
$instance = $evo->activeInstanceName();

echo "Instance: {$instance}\n";
echo "Laravel webhook URL: " . $evo->webhookUrl($instance) . "\n";
echo "APP_URL: " . config('app.url') . "\n\n";

try {
    $webhook = $evo->client()->getWebhook($instance);
    echo "Evolution webhook config:\n";
    echo json_encode($webhook, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $e) {
    echo "Failed to fetch webhook: " . $e->getMessage() . "\n";
}
