<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FilebrowserUpstreamResolver
{
    public function __construct(
        protected FilebrowserContainerResolver $containerResolver,
        protected CoolifySshExecutor $ssh,
        protected CoolifySettingsService $settings
    ) {}

    public function resolve(CoolifyWordpressSite $site, bool $refresh = false): ?string
    {
        $cacheKey = 'filebrowser_upstream:'.$site->uuid;
        if (! $refresh) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }

            $metaUrl = trim((string) (($site->metadata ?? [])['filebrowser_upstream_url'] ?? ''));
            if ($metaUrl !== '' && $this->isReachable($metaUrl)) {
                Cache::put($cacheKey, $metaUrl, 3600);

                return $metaUrl;
            }
        }

        foreach ($this->publicCandidateUrls($site) as $url) {
            if ($this->isReachable($url)) {
                $this->rememberUpstream($site, $url, $cacheKey);

                return $url;
            }
        }

        $sshUrl = $this->resolveViaSshPublishedPort($site);
        if ($sshUrl !== null && $this->isReachable($sshUrl)) {
            $this->rememberUpstream($site, $sshUrl, $cacheKey);

            return $sshUrl;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public function publicCandidateUrls(CoolifyWordpressSite $site): array
    {
        $metadata = $site->metadata ?? [];
        $urls = [];

        foreach ([
            $metadata['filebrowser_coolify_url'] ?? null,
            $metadata['filebrowser_url'] ?? null,
            $metadata['filebrowser_custom_url'] ?? null,
            $this->settings->buildWordpressFilebrowserPublicUrl($site->slug),
        ] as $raw) {
            $url = $this->normalizeBaseUrl((string) $raw);
            if ($url !== '') {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    protected function rememberUpstream(CoolifyWordpressSite $site, string $url, string $cacheKey): void
    {
        Cache::put($cacheKey, $url, 3600);
        $metadata = $site->metadata ?? [];
        if (($metadata['filebrowser_upstream_url'] ?? '') !== $url) {
            $site->update([
                'metadata' => array_merge($metadata, [
                    'filebrowser_upstream_url' => $url,
                ]),
            ]);
        }
    }

    protected function resolveViaSshPublishedPort(CoolifyWordpressSite $site): ?string
    {
        $resolved = $this->containerResolver->resolve($site);
        if (! ($resolved['success'] ?? false)) {
            return null;
        }

        $host = (string) ($resolved['host'] ?? '');
        $containerId = (string) ($resolved['container_id'] ?? '');
        if ($host === '' || $containerId === '') {
            return null;
        }

        $cmd = sprintf('docker port %s 80/tcp 2>/dev/null | head -n 1', escapeshellarg($containerId));
        $result = $this->ssh->run($host, $cmd, 30);
        $line = trim($result['output'] ?? '');
        if ($line === '' || ! preg_match('/:(\d+)\s*$/', $line, $m)) {
            return null;
        }

        $port = (int) $m[1];
        if ($port < 1) {
            return null;
        }

        return 'http://'.$host.':'.$port;
    }

    protected function isReachable(string $url): bool
    {
        try {
            $response = Http::withOptions([
                'verify' => false,
                'connect_timeout' => 8,
            ])
                ->timeout(12)
                ->get(rtrim($url, '/').'/api/settings');

            if ($response->successful()) {
                return true;
            }

            $response = Http::withOptions([
                'verify' => false,
                'connect_timeout' => 8,
            ])
                ->timeout(12)
                ->get(rtrim($url, '/').'/login');

            return $response->status() < 500;
        } catch (\Throwable $e) {
            Log::debug('FileBrowser upstream probe failed', ['url' => $url, 'error' => $e->getMessage()]);

            return false;
        }
    }

    protected function normalizeBaseUrl(string $url): string
    {
        $url = trim($url);

        return $url !== '' ? rtrim($url, '/') : '';
    }
}
