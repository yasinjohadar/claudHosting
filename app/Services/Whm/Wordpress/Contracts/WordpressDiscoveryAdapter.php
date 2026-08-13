<?php

namespace App\Services\Whm\Wordpress\Contracts;

use App\Models\WhmAccount;

interface WordpressDiscoveryAdapter
{
    /**
     * @return array{
     *   success: bool,
     *   available?: bool,
     *   message?: string,
     *   sites?: array<int, array{
     *     external_id: string,
     *     domain?: ?string,
     *     path?: ?string,
     *     url?: ?string,
     *     wp_version?: ?string,
     *     title?: ?string,
     *     metadata?: array<string, mixed>
     *   }>
     * }
     */
    public function discover(WhmAccount $account): array;

    public function source(): string;
}
