<?php

namespace App\Console\Commands;

use App\Services\Infrastructure\Netcup\NetcupScpClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class SyncNetcupOpenApiCommand extends Command
{
    protected $signature = 'netcup:sync-openapi';

    protected $description = 'Download and store Netcup SCP OpenAPI specification locally';

    public function handle(NetcupScpClient $client): int
    {
        $this->info('Fetching OpenAPI spec…');

        $res = $client->request('GET', '/openapi');
        if (! ($res['success'] ?? false)) {
            $url = rtrim($client->baseUrl(), '/').'/openapi';
            $fallback = Http::timeout(60)->acceptJson()->get($url);
            if (! $fallback->successful()) {
                $this->error($res['message'] ?? 'Failed to download OpenAPI spec');

                return self::FAILURE;
            }
            $body = $fallback->json();
        } else {
            $body = $res['body'];
        }

        $dir = storage_path('app/netcup');
        File::ensureDirectoryExists($dir);
        $path = $dir.'/openapi.json';
        File::put($path, json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Saved to '.$path);

        return self::SUCCESS;
    }
}
