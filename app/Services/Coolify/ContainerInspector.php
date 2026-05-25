<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;

class ContainerInspector
{
    public function __construct(
        protected ContainerContextFactory $contextFactory,
        protected CoolifySshExecutor $ssh
    ) {}

    /**
     * @return array{success: bool, data?: array<string, mixed>, message?: string}
     */
    public function inspect(CoolifyWordpressSite $site): array
    {
        $ctx = $this->contextFactory->forSite($site);
        if (! ($ctx['success'] ?? false)) {
            return ['success' => false, 'message' => $ctx['message'] ?? 'غير متاح'];
        }

        /** @var ContainerExecutionContext $context */
        $context = $ctx['context'];
        $id = escapeshellarg($context->containerId);

        $inspect = $this->ssh->run($context->host, 'docker inspect '.$id, 60);
        $compose = $this->composeStatus($context);

        return [
            'success' => true,
            'data' => [
                'container_id' => $context->containerId,
                'container_name' => $context->containerName,
                'wordpress_root' => $context->wordpressRoot,
                'host' => $context->host,
                'inspect_raw' => $inspect['output'] ?? '',
                'compose' => $compose,
            ],
        ];
    }

    /**
     * @return array{success: bool, logs?: string, message?: string}
     */
    public function containerLogs(CoolifyWordpressSite $site, int $tail = 500): array
    {
        $ctx = $this->contextFactory->forSite($site);
        if (! ($ctx['success'] ?? false)) {
            return ['success' => false, 'message' => $ctx['message'] ?? 'غير متاح'];
        }

        /** @var ContainerExecutionContext $context */
        $context = $ctx['context'];
        $tail = max(50, min(2000, $tail));
        $cmd = sprintf('docker logs --tail %d %s 2>&1', $tail, escapeshellarg($context->containerId));
        $result = $this->ssh->run($context->host, $cmd, 90);

        return [
            'success' => $result['success'] ?? false,
            'logs' => $result['output'] ?? '',
            'message' => ($result['success'] ?? false) ? null : ($result['output'] ?: 'فشل جلب السجلات'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function composeStatus(ContainerExecutionContext $context): array
    {
        $uuid = preg_replace('/[^a-zA-Z0-9_-]/', '', $context->serviceUuid);
        if ($uuid === '') {
            return ['available' => false];
        }

        foreach ([
            '/data/coolify/services/'.$uuid,
            '/var/lib/coolify/services/'.$uuid,
        ] as $dir) {
            $dirQ = escapeshellarg($dir);
            $check = $this->ssh->run($context->host, 'test -f '.$dirQ.'/docker-compose.yml && echo yes', 15);
            if (! str_contains($check['output'] ?? '', 'yes')) {
                continue;
            }
            $ps = $this->ssh->run($context->host, 'cd '.$dirQ.' && docker compose ps 2>&1', 45);

            return [
                'available' => true,
                'dir' => $dir,
                'ps' => $ps['output'] ?? '',
            ];
        }

        return ['available' => false];
    }
}
