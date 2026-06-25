<?php

namespace Tests\Unit\CyberPanel;

use App\Models\CyberPanelWordpressSite;
use App\Services\CyberPanel\CyberPanelApiService;
use App\Services\CyberPanel\CyberPanelWordpressService;
use Illuminate\Support\Facades\Crypt;
use Mockery;
use Tests\TestCase;

class CyberPanelWordpressServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_resolve_login_prefers_cyberpanel_sso_over_stored_credentials(): void
    {
        $api = Mockery::mock(CyberPanelApiService::class);
        $api->shouldReceive('isConfigured')->andReturn(true);
        $api->shouldReceive('wordpressAutoLogin')
            ->once()
            ->with('s1.example.com')
            ->andReturn([
                'success' => true,
                'data' => ['status' => 1, 'password' => 'sso-temp-pass'],
            ]);

        $site = Mockery::mock(CyberPanelWordpressSite::class)->makePartial();
        $site->domain = 's1.example.com';
        $site->wp_user = 'root';
        $site->shouldReceive('getAdminPassword')->andReturn('stored-pass');
        $site->shouldReceive('storeAdminPassword')
            ->once()
            ->with('sso-temp-pass', 'cyberpanel');

        $service = new CyberPanelWordpressService($api);
        $result = $service->resolveLoginCredentials($site);

        $this->assertTrue($result['success']);
        $this->assertSame('cyberpanel', $result['username']);
        $this->assertSame('sso-temp-pass', $result['password']);
        $this->assertSame('cyberpanel_auto_login', $result['source']);
    }

    public function test_resolve_login_falls_back_to_stored_when_sso_fails(): void
    {
        $api = Mockery::mock(CyberPanelApiService::class);
        $api->shouldReceive('isConfigured')->andReturn(true);
        $api->shouldReceive('wordpressAutoLogin')
            ->once()
            ->andReturn(['success' => false, 'message' => 'API down']);

        $site = new CyberPanelWordpressSite([
            'domain' => 's1.example.com',
            'wp_user' => 'admin',
            'metadata' => [
                'wp_admin_password_enc' => Crypt::encryptString('local-pass'),
            ],
        ]);

        $service = new CyberPanelWordpressService($api);
        $result = $service->resolveLoginCredentials($site);

        $this->assertTrue($result['success']);
        $this->assertSame('admin', $result['username']);
        $this->assertSame('local-pass', $result['password']);
        $this->assertSame('stored', $result['source']);
    }
}
