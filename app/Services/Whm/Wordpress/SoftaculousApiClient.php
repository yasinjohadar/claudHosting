<?php

namespace App\Services\Whm\Wordpress;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Authenticate to cPanel Softaculous end-user API via a one-time WHM user session.
 */
class SoftaculousApiClient
{
    /**
     * @return array{success: bool, message?: string, data?: mixed, base_url?: string, cookies?: CookieJar}
     */
    public function call(string $sessionUrl, string $query, int $timeout = 60): array
    {
        try {
            $jar = new CookieJar;
            $landing = Http::withOptions([
                'verify' => false,
                'cookies' => $jar,
                'allow_redirects' => true,
            ])
                ->timeout($timeout)
                ->withHeaders(['User-Agent' => 'claudHosting-Softaculous/1.0'])
                ->get($sessionUrl);

            $effectiveUrl = (string) ($landing->effectiveUri() ?? $sessionUrl);
            $bases = $this->candidateBases($effectiveUrl, $sessionUrl);
            $lastError = 'تعذر الوصول إلى Softaculous';

            foreach ($bases as $base) {
                $apiUrl = $base.(str_contains($base, '?') ? '&' : '?').ltrim($query, '?&');
                $response = Http::withOptions([
                    'verify' => false,
                    'cookies' => $jar,
                ])
                    ->timeout($timeout)
                    ->withHeaders(['User-Agent' => 'claudHosting-Softaculous/1.0'])
                    ->get($apiUrl);

                if (! $response->successful()) {
                    $lastError = 'Softaculous HTTP '.$response->status().' @ '.$base;
                    continue;
                }

                $json = $response->json();
                if (! is_array($json)) {
                    $body = Str::lower(trim($response->body()));
                    if (str_contains($body, 'not found') || str_contains($body, '404')) {
                        $lastError = 'مسار Softaculous غير موجود';
                        continue;
                    }
                    $lastError = 'استجابة Softaculous غير JSON';
                    continue;
                }

                return [
                    'success' => true,
                    'data' => $json,
                    'base_url' => $base,
                    'cookies' => $jar,
                ];
            }

            return ['success' => false, 'message' => $lastError];
        } catch (\Throwable $e) {
            Log::warning('Softaculous API error', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return list<string>
     */
    protected function candidateBases(string $effectiveUrl, string $sessionUrl): array
    {
        $roots = [];
        foreach ([$effectiveUrl, $sessionUrl] as $url) {
            if (preg_match('#^(https?://[^/]+(?:/cpsess[^/]+)?)#i', $url, $m)) {
                $roots[] = rtrim($m[1], '/');
            }
        }

        $candidates = [];
        foreach (array_unique($roots) as $root) {
            foreach (['jupiter', 'paper_lantern', 'jupiter_dark', 'retro', 'x3'] as $theme) {
                $candidates[] = $root.'/frontend/'.$theme.'/softaculous/index.live.php';
            }
        }

        return array_values(array_unique($candidates));
    }
}
