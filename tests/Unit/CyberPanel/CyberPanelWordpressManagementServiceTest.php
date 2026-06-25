<?php

namespace Tests\Unit\CyberPanel;

use App\Models\CyberPanelWordpressSite;
use App\Services\CyberPanel\CyberPanelApiService;
use App\Services\CyberPanel\CyberPanelSettingsService;
use App\Services\CyberPanel\CyberPanelWordpressManagementService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class CyberPanelWordpressManagementServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    protected function makeApi(array $connection = []): CyberPanelApiService
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
        $settings->shouldReceive('buildCyberPanelDeepLinks')->andReturn([
            'panel' => 'https://cp.example.com:8090/',
            'file_manager' => 'https://cp.example.com:8090/filemanager/test.com',
            'wp_manager' => 'https://cp.example.com:8090/websites/test.com/wordpress',
            'websites' => 'https://cp.example.com:8090/websites/listWebsites',
        ]);

        return new CyberPanelApiService($settings);
    }

    public function test_get_wp_plugins_parses_json_list(): void
    {
        Http::fake([
            'https://cp.example.com:8090/cloudAPI/*' => Http::response([
                'status' => 1,
                'fetchStatus' => 1,
                'data' => json_encode([
                    ['name' => 'akismet', 'status' => 'active', 'version' => '5.0', 'update' => 'none'],
                ]),
            ], 200),
        ]);

        $api = $this->makeApi();
        $result = $api->getWpPlugins('test.com');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('akismet', $result['data'][0]['name']);
    }

    public function test_management_state_ready_when_running_and_api_configured(): void
    {
        Http::fake();

        $api = Mockery::mock(CyberPanelApiService::class);
        $api->shouldReceive('isConfigured')->andReturn(true);
        $api->shouldReceive('supportsCloudOperations')->andReturn(true);

        $settings = Mockery::mock(CyberPanelSettingsService::class);
        $service = new CyberPanelWordpressManagementService($api, $settings);

        $site = new CyberPanelWordpressSite(['domain' => 'test.com', 'status' => 'running']);
        $state = $service->getManagementState($site);

        $this->assertTrue($state['ui_ready']);
        $this->assertTrue($state['execute_ready']);
    }

    public function test_execute_plugin_activate_calls_change_state(): void
    {
        Http::fake([
            'https://cp.example.com:8090/cloudAPI/*' => Http::sequence()
                ->push(['status' => 1, 'message' => 'Plugin successfully activated.'], 200)
                ->push(['status' => 1, 'data' => '[]'], 200)
                ->push(['status' => 1, 'data' => '[]'], 200),
        ]);

        $settings = Mockery::mock(CyberPanelSettingsService::class);
        $settings->shouldReceive('getConnectionConfig')->andReturn([
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
        ]);
        $settings->shouldReceive('clearCache')->andReturnNull();

        $api = new CyberPanelApiService($settings);
        $mgmt = new CyberPanelWordpressManagementService($api, $settings);

        $site = Mockery::mock(CyberPanelWordpressSite::class)->makePartial();
        $site->domain = 'test.com';
        $site->status = 'running';
        $site->metadata = [];
        $site->shouldReceive('update')->andReturnTrue();
        $site->shouldReceive('fresh')->andReturnSelf();

        $result = $mgmt->executeAction($site, 'plugin_activate', ['slug' => 'akismet'], 1);

        $this->assertTrue($result['success']);
    }

    public function test_core_reinstall_calls_installwpcore_via_session(): void
    {
        Http::fake([
            'https://cp.example.com:8090/cloudAPI/*' => Http::response([
                'status' => 1,
                'listWebSiteStatus' => 1,
                'data' => json_encode([[
                    'domain' => 'test.com',
                    'wp_sites' => [['id' => 42, 'url' => 'test.com', 'title' => 'test.com']],
                ]]),
            ], 200),
            'https://cp.example.com:8090/api/loginAPI' => Http::response('', 302, [
                'Set-Cookie' => 'sessionid=test-session; Path=/',
            ]),
            'https://cp.example.com:8090/websiteFunctions/ListWPSites' => Http::response(
                '<html>$scope.wpSites = [{"id":42,"url":"test.com"}];</html>',
                200,
                ['Set-Cookie' => 'csrftoken=csrf-test; Path=/'],
            ),
            'https://cp.example.com:8090/websiteFunctions/installwpcore' => Http::response([
                'status' => 1,
                'installStatus' => 1,
                'error_message' => 'None',
                'result' => 'Success: WordPress downloaded.',
            ], 200),
        ]);

        $api = $this->makeApi();
        $result = $api->reinstallWpCore('test.com');

        $this->assertTrue($result['success']);
        $this->assertSame(42, $result['data']['wp_id']);
        $this->assertSame('cyberpanel_wp_manager', $result['data']['method']);

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://cp.example.com:8090/websiteFunctions/installwpcore') {
                return false;
            }
            $body = $request->body();
            $decoded = json_decode($body, true);

            return is_array($decoded) && ($decoded['WPid'] ?? null) === 42;
        });
    }

    public function test_core_reinstall_scans_and_resolves_from_list_when_cloud_empty(): void
    {
        Http::fake([
            'https://cp.example.com:8090/cloudAPI/*' => Http::sequence()
                ->push(['status' => 1, 'data' => json_encode([['domain' => 'test.com', 'wp_sites' => []]])], 200)
                ->push(['status' => 1, 'data' => json_encode([['domain' => 'test.com', 'wp_sites' => [['id' => 7, 'url' => 'test.com']]]])], 200),
            'https://cp.example.com:8090/api/loginAPI' => Http::response('', 302, [
                'Set-Cookie' => 'sessionid=test-session; Path=/',
            ]),
            'https://cp.example.com:8090/websiteFunctions/ListWPSites' => Http::response(
                '<html>$scope.wpSites = [{"id":7,"url":"test.com"}];</html>',
                200,
                ['Set-Cookie' => 'csrftoken=csrf-test; Path=/'],
            ),
            'https://cp.example.com:8090/websiteFunctions/ScanWordpressSite' => Http::response([
                'status' => 1,
                'error_message' => 'None',
            ], 200),
            'https://cp.example.com:8090/websiteFunctions/installwpcore' => Http::response([
                'status' => 1,
                'installStatus' => 1,
                'error_message' => 'None',
            ], 200),
        ]);

        $api = $this->makeApi();
        $result = $api->reinstallWpCore('test.com');

        $this->assertTrue($result['success']);
        $this->assertSame(7, $result['data']['wp_id']);
    }

    public function test_core_reinstall_falls_back_to_wordpress_dashboard_when_wp_manager_unresolved(): void
    {
        Http::fake([
            'https://cp.example.com:8090/cloudAPI/*' => Http::sequence()
                ->push(['status' => 1, 'data' => json_encode([['domain' => 'test.com', 'wp_sites' => []]])], 200)
                ->push(['status' => 1, 'data' => json_encode([['domain' => 'test.com', 'wp_sites' => []]])], 200)
                ->push(['status' => 1, 'fetchStatus' => 1, 'data' => json_encode([])], 200)
                ->push(['status' => 1, 'password' => 'wp-secret'], 200),
            'https://cp.example.com:8090/api/loginAPI' => Http::response('', 302, [
                'Set-Cookie' => 'sessionid=test-session; Path=/',
            ]),
            'https://cp.example.com:8090/websiteFunctions/*' => Http::response('<html></html>', 404),
            'https://test.com/wp-login.php' => Http::sequence()
                ->push('<form></form>', 200)
                ->push('', 302),
            'https://test.com/wp-admin/update-core.php*' => Http::sequence()
                ->push(
                    '<a href="update-core.php?action=do-core-reinstall&amp;_wpnonce=abc123">Reinstall</a>',
                    200
                )
                ->push('<html>WordPress updated successfully</html>', 200),
        ]);

        $api = $this->makeApi(['api_token' => 'test-token', 'token_configured' => true]);
        $result = $api->reinstallWpCore('test.com');

        $this->assertTrue($result['success']);
        $this->assertSame('wordpress_dashboard', $result['data']['method']);
    }
}
