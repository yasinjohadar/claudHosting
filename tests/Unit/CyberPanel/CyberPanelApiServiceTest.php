<?php

namespace Tests\Unit\CyberPanel;

use App\Services\CyberPanel\CyberPanelApiService;
use App\Services\CyberPanel\CyberPanelSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class CyberPanelApiServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    protected function makeService(array $connection): CyberPanelApiService
    {
        $settings = Mockery::mock(CyberPanelSettingsService::class);
        $settings->shouldReceive('getConnectionConfig')->andReturn(array_merge([
            'host' => 'cp.example.com',
            'port' => 8090,
            'admin_user' => 'admin',
            'admin_password' => 'secret',
            'api_token' => '',
            'api_style' => 'cloud',
            'verify_ssl' => false,
            'default_package' => 'Default',
            'default_php_version' => 'PHP 8.3',
            'default_owner' => 'admin',
            'default_domain_suffix' => '',
            'timeout' => 30,
            'password_configured' => true,
            'token_configured' => false,
        ], $connection));
        $settings->shouldReceive('clearCache')->andReturnNull();

        return new CyberPanelApiService($settings);
    }

    public function test_verify_connection_posts_to_cloud_api(): void
    {
        Http::fake([
            'https://cp.example.com:8090/api/verifyConn' => Http::response(['status' => 1, 'message' => 'OK'], 200),
        ]);

        $service = $this->makeService([]);
        $result = $service->verifyConnection();

        $this->assertTrue($result['success']);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/verifyConn')
                && $request['adminUser'] === 'admin'
                && $request['adminPass'] === 'secret';
        });
    }

    public function test_create_website_uses_cloud_controller(): void
    {
        Http::fake([
            'https://cp.example.com:8090/cloudAPI/*' => Http::response([
                'status' => 1,
                'message' => 'Website created',
            ], 200),
        ]);

        $service = $this->makeService([]);
        $result = $service->createWebsite([
            'domain' => 'client.example.com',
            'package' => 'Default',
            'email' => 'user@example.com',
            'owner' => 'admin',
            'php_version' => 'PHP 8.3',
        ]);

        $this->assertTrue($result['success']);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/cloudAPI/')
                && $request['controller'] === 'submitWebsiteCreation'
                && $request['domainName'] === 'client.example.com'
                && $request['package'] === 'Default';
        });
    }

    public function test_deploy_wordpress_uses_deploy_controller(): void
    {
        Http::fake([
            'https://cp.example.com:8090/cloudAPI/*' => Http::response([
                'status' => 1,
                'tempStatusPath' => '/home/cyberpanel/1234',
            ], 200),
        ]);

        $service = $this->makeService(['api_token' => 'test-token']);
        $result = $service->deployWordPress([
            'domain' => 'client.example.com',
            'admin_email' => 'user@example.com',
            'admin_user' => 'admin',
            'title' => 'My Site',
        ]);

        $this->assertTrue($result['success']);
        Http::assertSent(function ($request) {
            return $request['controller'] === 'DeployWordPress'
                && $request['domain'] === 'client.example.com'
                && $request['createSite'] === 0;
        });
    }

    public function test_issue_ssl_sends_virtual_host(): void
    {
        Http::fake([
            'https://cp.example.com:8090/cloudAPI/*' => Http::response(['status' => 1, 'SSL' => 1], 200),
        ]);

        $service = $this->makeService(['api_token' => 'test-token']);
        $result = $service->issueSsl('client.example.com');

        $this->assertTrue($result['success']);
        Http::assertSent(function ($request) {
            return $request['controller'] === 'issueSSL'
                && $request['virtualHost'] === 'client.example.com';
        });
    }

    public function test_submit_website_status_suspend(): void
    {
        Http::fake([
            'https://cp.example.com:8090/cloudAPI/*' => Http::response(['status' => 1], 200),
        ]);

        $service = $this->makeService([]);
        $result = $service->suspendWebsite('client.example.com');

        $this->assertTrue($result['success']);
        Http::assertSent(function ($request) {
            return $request['controller'] === 'submitWebsiteStatus'
                && $request['websiteName'] === 'client.example.com'
                && $request['state'] === 'Suspend';
        });
    }

    public function test_list_packages_normalizes_response(): void
    {
        Http::fake([
            'https://cp.example.com:8090/cloudAPI/*' => Http::response([
                'status' => 1,
                'packages' => [
                    ['packageName' => 'Default', 'diskSpace' => 1000],
                ],
            ], 200),
        ]);

        $service = $this->makeService([]);
        $result = $service->listPackages();

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['packages']);
        $this->assertSame('Default', $result['packages'][0]['packageName']);
    }

    public function test_returns_error_when_host_not_configured(): void
    {
        $service = $this->makeService(['host' => '']);
        $result = $service->cloudRequest('verifyConn', []);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('غير مضبوط', $result['message']);
    }

    public function test_get_wp_users_via_rest_api(): void
    {
        Http::fake([
            'https://cp.example.com:8090/cloudAPI/*' => Http::sequence()
                ->push(['status' => 1, 'fetchStatus' => 1, 'data' => json_encode([])], 200)
                ->push(['status' => 1, 'password' => 'wp-secret'], 200),
            'https://test.com/wp-login.php' => Http::sequence()
                ->push('<form></form>', 200)
                ->push('', 302),
            'https://test.com/wp-admin/' => Http::response(
                '<script>var wpApiSettings = {"root":"https://test.com/wp-json/","nonce":"abc123"};</script>',
                200
            ),
            'https://test.com/wp-json/wp/v2/users*' => Http::response([
                ['id' => 1, 'slug' => 'admin', 'email' => 'a@test.com', 'name' => 'Admin', 'roles' => ['administrator']],
            ], 200),
        ]);

        $service = $this->makeService(['api_token' => 'test-token', 'token_configured' => true]);
        $result = $service->getWpUsers('test.com');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('admin', $result['data'][0]['user_login']);
    }
}
