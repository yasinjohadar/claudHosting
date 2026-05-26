<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use App\Services\CoolifyApiService;

class FilebrowserContainerResolver
{
    public function __construct(
        protected WordpressContainerResolver $wordpressResolver,
        protected CoolifyApiService $coolify,
        protected CoolifySshExecutor $ssh
    ) {}

    /**
     * @return array{
     *     success: bool,
     *     host?: string,
     *     container_id?: string,
     *     container_name?: string,
     *     message?: string
     * }
     */
    public function resolve(CoolifyWordpressSite $site, bool $forceRefresh = false): array
    {
        $wp = $this->wordpressResolver->resolve($site, $forceRefresh);
        if (! ($wp['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $wp['message'] ?? 'تعذّر تحديد السيرفر (SSH)',
            ];
        }

        $host = (string) ($wp['host'] ?? '');
        $metadata = $site->metadata ?? [];
        $cachedId = trim((string) ($metadata['filebrowser_container_id'] ?? ''));

        if (! $forceRefresh && $cachedId !== '' && $this->isContainerRunning($host, $cachedId)) {
            return [
                'success' => true,
                'host' => $host,
                'container_id' => $cachedId,
                'container_name' => (string) ($metadata['filebrowser_container_name'] ?? ''),
            ];
        }

        $hints = $this->collectHints($site);
        $container = $this->findFilebrowserContainer($host, $hints);
        if ($container === null) {
            return [
                'success' => false,
                'message' => 'لم تُعثر على حاوية FileBrowser قيد التشغيل على السيرفر.',
            ];
        }

        $site->update([
            'metadata' => array_merge($metadata, [
                'filebrowser_container_id' => $container['id'],
                'filebrowser_container_name' => $container['name'],
                'filebrowser_resolved_at' => now()->toIso8601String(),
            ]),
        ]);

        return [
            'success' => true,
            'host' => $host,
            'container_id' => $container['id'],
            'container_name' => $container['name'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function collectHints(CoolifyWordpressSite $site): array
    {
        $hints = [
            strtolower($site->slug),
            'filebrowser',
        ];

        if (filled($site->service_uuid)) {
            $hints[] = (string) $site->service_uuid;
            try {
                $service = $this->coolify->getService((string) $site->service_uuid);
                $data = $service['data'] ?? $service;
                if (is_array($data)) {
                    $hints[] = strtolower((string) ($data['name'] ?? ''));
                    foreach ($data['applications'] ?? [] as $app) {
                        if (! is_array($app)) {
                            continue;
                        }
                        foreach (['name', 'uuid'] as $key) {
                            $v = trim((string) ($app[$key] ?? ''));
                            if ($v !== '') {
                                $hints[] = strtolower($v);
                            }
                        }
                    }
                }
            } catch (\Throwable) {
                // optional
            }
        }

        return array_values(array_unique(array_filter($hints)));
    }

    /**
     * @param  array<int, string>  $hints
     * @return array{id: string, name: string}|null
     */
    protected function findFilebrowserContainer(string $host, array $hints): ?array
    {
        foreach ([
            'docker ps --format "{{.ID}}\t{{.Names}}\t{{.Image}}\t{{.Status}}"',
            'docker ps --format "{{.ID}}\t{{.Names}}\t{{.Image}}"',
        ] as $command) {
            $result = $this->ssh->run($host, $command, 60);
            if (! ($result['success'] ?? false)) {
                continue;
            }

            $container = $this->parseDockerPsOutput($result['output'] ?? '', $hints, $host);
            if ($container !== null) {
                return $container;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $hints
     * @return array{id: string, name: string}|null
     */
    protected function parseDockerPsOutput(string $output, array $hints, string $host): ?array
    {
        $candidates = [];

        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, 'CONTAINER')) {
                continue;
            }

            $parts = str_contains($line, "\t") ? explode("\t", $line) : preg_split('/\s{2,}/', $line);
            if (count($parts) < 3) {
                continue;
            }

            $id = trim($parts[0]);
            if (! preg_match('/^[a-f0-9]+$/i', $id)) {
                continue;
            }

            $name = strtolower(trim($parts[1]));
            $image = strtolower(trim($parts[2]));
            $status = strtolower(trim($parts[3] ?? 'running'));

            if (! str_contains($name, 'filebrowser') && ! str_contains($image, 'filebrowser')) {
                continue;
            }

            if (! $this->isDockerStatusRunning($status) && ! $this->isContainerRunning($host, $id)) {
                continue;
            }

            $score = 20;
            foreach ($hints as $hint) {
                $hint = strtolower($hint);
                if ($hint !== '' && str_contains($name, $hint)) {
                    $score += 5;
                }
            }

            $candidates[] = [
                'id' => $id,
                'name' => trim($parts[1]),
                'score' => $score,
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        return [
            'id' => $candidates[0]['id'],
            'name' => $candidates[0]['name'],
        ];
    }

    protected function isDockerStatusRunning(string $status): bool
    {
        return str_contains($status, 'up') || str_contains($status, 'running');
    }

    protected function isContainerRunning(string $host, string $containerId): bool
    {
        $id = preg_replace('/[^a-f0-9]/i', '', $containerId) ?: $containerId;
        $format = '{{.State.Running}}';
        $result = $this->ssh->run(
            $host,
            'docker inspect -f '.escapeshellarg($format).' '.escapeshellarg($id).' 2>/dev/null',
            30
        );

        return ($result['success'] ?? false) && trim($result['output'] ?? '') === 'true';
    }
}
