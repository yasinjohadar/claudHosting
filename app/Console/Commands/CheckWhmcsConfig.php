<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckWhmcsConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whmcs:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate WHMCS configuration and basic connectivity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking WHMCS configuration...');

        $apiUrl = config('whmcs.api_url') ?: env('WHMCS_API_URL');
        $identifier = config('whmcs.api_identifier') ?: env('WHMCS_API_IDENTIFIER');
        $secret = config('whmcs.api_secret') ?: env('WHMCS_API_SECRET');
        $accessToken = config('whmcs.access_token') ?: env('WHMCS_ACCESS_TOKEN');

        $errors = [];

        if (empty($apiUrl)) {
            $errors[] = 'WHMCS API URL is not set (WHMCS_API_URL)';
        } else {
            $this->line("API URL: {$apiUrl}");
            try {
                $response = Http::timeout(5)->withOptions(['verify' => false])->head($apiUrl);
                $status = $response->status();
                if ($response->successful() || in_array($status, [200, 302, 401, 403, 405])) {
                    $this->info("API URL reachable (HTTP {$status})");
                } else {
                    $errors[] = "API URL returned HTTP status {$status}";
                }
            } catch (\Exception $e) {
                $errors[] = 'Unable to reach API URL: ' . $e->getMessage();
            }
        }

        if (empty($accessToken) && (empty($identifier) || empty($secret))) {
            $errors[] = 'Authentication not configured. Set WHMCS_ACCESS_TOKEN or WHMCS_API_IDENTIFIER and WHMCS_API_SECRET';
        } else {
            $this->info('Authentication configured: ' . (empty($accessToken) ? 'identifier/secret' : 'access token'));
        }

        if (!empty($errors)) {
            $this->error('Configuration issues found:');
            foreach ($errors as $err) {
                $this->line(' - ' . $err);
            }

            return 1;
        }

        $this->info('Basic WHMCS configuration checks passed.');
        $this->line('Next steps: run sync jobs or configure webhooks for real-time updates.');

        return 0;
    }
}
