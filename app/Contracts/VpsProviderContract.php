<?php

namespace App\Contracts;

interface VpsProviderContract
{
    public function providerKey(): string;

    /**
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array;

    /**
     * @return array{success: bool, message?: string, instances: array<int, array<string, mixed>>}
     */
    public function listInstances(): array;

    /**
     * @return array{success: bool, message?: string, instance?: array<string, mixed>}
     */
    public function getInstance(string $externalId): array;

    /**
     * @return array{success: bool, message: string}
     */
    public function start(string $externalId): array;

    /**
     * @return array{success: bool, message: string}
     */
    public function stop(string $externalId): array;

    /**
     * @return array{success: bool, message: string}
     */
    public function shutdown(string $externalId): array;

    /**
     * @return array{success: bool, message: string}
     */
    public function restart(string $externalId): array;
}
