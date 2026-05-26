<?php

namespace Tests\Unit;

use App\Services\Coolify\FilebrowserCredentialService;
use PHPUnit\Framework\TestCase;

class FilebrowserCredentialServiceTest extends TestCase
{
    public function test_parse_password_from_logs(): void
    {
        $service = new FilebrowserCredentialService(
            $this->createMock(\App\Services\Coolify\FilebrowserContainerResolver::class),
            $this->createMock(\App\Services\Coolify\CoolifySshExecutor::class),
            $this->createMock(\App\Services\Coolify\CoolifySettingsService::class)
        );

        $logs = "2026/05/26 11:37:07 User 'admin' initialized with randomly generated password: vm9NmjwBExoMBcVN\n";
        $parsed = $service->parsePasswordFromLogs($logs);

        $this->assertIsArray($parsed);
        $this->assertSame('admin', $parsed['username']);
        $this->assertSame('vm9NmjwBExoMBcVN', $parsed['password']);
    }

    public function test_parse_password_from_logs_returns_null_when_missing(): void
    {
        $service = new FilebrowserCredentialService(
            $this->createMock(\App\Services\Coolify\FilebrowserContainerResolver::class),
            $this->createMock(\App\Services\Coolify\CoolifySshExecutor::class),
            $this->createMock(\App\Services\Coolify\CoolifySettingsService::class)
        );

        $this->assertNull($service->parsePasswordFromLogs('Listening on [::]:80'));
    }

    public function test_has_stored_credentials(): void
    {
        $service = new FilebrowserCredentialService(
            $this->createMock(\App\Services\Coolify\FilebrowserContainerResolver::class),
            $this->createMock(\App\Services\Coolify\CoolifySshExecutor::class),
            $this->createMock(\App\Services\Coolify\CoolifySettingsService::class)
        );

        $this->assertFalse($service->hasStoredCredentials([]));
        $this->assertFalse($service->hasStoredCredentials(['filebrowser_username' => 'admin']));
        $this->assertTrue($service->hasStoredCredentials([
            'filebrowser_username' => 'admin',
            'filebrowser_password' => 'encrypted',
        ]));
    }
}
