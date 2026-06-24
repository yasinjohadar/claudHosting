<?php

namespace Tests\Unit\Infrastructure;

use App\Services\Infrastructure\Netcup\Concerns\NetcupScpHelpers;
use PHPUnit\Framework\TestCase;

class NetcupScpHelpersTest extends TestCase
{
    public function test_wrap_response_extracts_task_uuid(): void
    {
        $helper = new class
        {
            use NetcupScpHelpers;

            public function wrap(array $clientRes): array
            {
                return $this->wrapResponse($clientRes);
            }
        };

        $wrapped = $helper->wrap([
            'success' => true,
            'body' => ['taskUuid' => 'abc-123', 'status' => 'PENDING'],
        ]);

        $this->assertTrue($wrapped['success']);
        $this->assertSame('abc-123', $wrapped['task_uuid']);
    }

    public function test_map_server_row_uses_live_state(): void
    {
        $helper = new class
        {
            use NetcupScpHelpers;

            public function map(array $row): ?array
            {
                return $this->mapServerRow($row);
            }
        };

        $mapped = $helper->map([
            'id' => 42,
            'nickname' => 'vps-test',
            'ipv4Addresses' => ['203.0.113.10'],
            'serverLiveInfo' => ['state' => 'RUNNING'],
            'location' => 'DE',
        ]);

        $this->assertNotNull($mapped);
        $this->assertSame('scp:42', $mapped['external_id']);
        $this->assertSame('running', $mapped['status']);
        $this->assertSame('203.0.113.10', $mapped['ip']);
    }

    public function test_parse_server_id_strips_prefix(): void
    {
        $helper = new class
        {
            use NetcupScpHelpers;

            public function parse(string $id): string
            {
                return $this->parseServerId($id);
            }
        };

        $this->assertSame('99', $helper->parse('scp:99'));
        $this->assertSame('99', $helper->parse('99'));
    }

    public function test_map_server_row_uses_interface_ipv4_and_name_fallback(): void
    {
        $helper = new class
        {
            use NetcupScpHelpers;

            public function map(array $row): ?array
            {
                return $this->mapServerRow($row);
            }
        };

        $mapped = $helper->map([
            'id' => 884492,
            'name' => '',
            'serverLiveInfo' => [
                'state' => 'RUNNING',
                'currentServerMemoryInMiB' => 6144,
                'maxServerMemoryInMiB' => 8192,
                'interfaces' => [
                    ['ipv4Addresses' => ['194.163.144.165']],
                ],
            ],
            'site' => ['name' => 'DE-NUE'],
        ]);

        $this->assertNotNull($mapped);
        $this->assertSame('Netcup 884492', $mapped['name']);
        $this->assertSame('194.163.144.165', $mapped['ip']);
        $this->assertSame('running', $mapped['status']);
        $this->assertSame('DE-NUE', $mapped['region']);
        $this->assertArrayNotHasKey('ram_percent', $mapped['metadata']['scp_live'] ?? []);
        $this->assertSame(8192, $mapped['metadata']['scp_live']['max_memory_mib'] ?? null);
    }
}
