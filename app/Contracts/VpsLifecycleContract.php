<?php

namespace App\Contracts;

interface VpsLifecycleContract
{
    /**
     * @return array{success: bool, message: string, images?: array<int, array<string, mixed>>}
     */
    public function listImages(string $externalId): array;

    /**
     * @param  array<string, mixed>  $options
     * @return array{success: bool, message: string}
     */
    public function reinstall(string $externalId, string $imageId, array $options = []): array;

    /**
     * @param  array<string, mixed>  $order
     * @return array{success: bool, message: string, external_id?: string}
     */
    public function createInstance(array $order): array;
}
