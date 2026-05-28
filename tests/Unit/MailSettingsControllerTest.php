<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\MailSettingsController;
use App\Services\Mail\MailSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class MailSettingsControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_test_connection_sends_raw_mail(): void
    {
        Mail::shouldReceive('raw')->once();

        $service = Mockery::mock(MailSettingsService::class);
        $service->shouldReceive('applyRuntimeConfig')->once();

        $controller = new MailSettingsController($service);
        $request = Request::create('/admin/mail-settings/test', 'POST', [
            'test_email' => 'tester@example.com',
        ]);

        $response = $controller->testConnection($request);

        $this->assertSame(302, $response->getStatusCode());
    }
}
