<?php

namespace Tests\Unit;

use App\Services\Mail\TemplateRendererService;
use Tests\TestCase;

class TemplateRendererServiceTest extends TestCase
{
    public function test_it_replaces_known_variables(): void
    {
        $service = new TemplateRendererService;

        $result = $service->render('Hello {{user_name}} - {{email}}', [
            'user_name' => 'Yasin',
            'email' => 'user@example.com',
        ]);

        $this->assertSame('Hello Yasin - user@example.com', $result);
    }

    public function test_it_keeps_unknown_variables_empty(): void
    {
        $service = new TemplateRendererService;

        $result = $service->render('Hello {{user_name}} {{missing_key}}', [
            'user_name' => 'Yasin',
        ]);

        $this->assertSame('Hello Yasin ', $result);
    }
}
