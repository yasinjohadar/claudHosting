<?php

namespace App\Services\Coolify;

readonly class ContainerExecutionContext
{
    public function __construct(
        public int $siteId,
        public string $siteUuid,
        public string $host,
        public string $containerId,
        public string $containerName,
        public string $wordpressRoot,
        public string $serviceUuid,
    ) {}

    public function absolutePath(string $relativePath): string
    {
        $root = rtrim($this->wordpressRoot, '/');
        $relative = trim(str_replace('\\', '/', $relativePath), '/');

        return $relative === '' ? $root : $root.'/'.$relative;
    }
}
