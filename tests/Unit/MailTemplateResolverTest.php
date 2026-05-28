<?php

namespace Tests\Unit;

use App\Services\Mail\MailTemplateResolver;
use Tests\TestCase;

class MailTemplateResolverTest extends TestCase
{
    public function test_defaults_include_core_template_keys(): void
    {
        $resolver = new MailTemplateResolver;
        $defaults = $resolver->defaults();

        $this->assertArrayHasKey('payment.received', $defaults);
        $this->assertArrayHasKey('auth.verify_email', $defaults);
        $this->assertArrayHasKey('auth.reset_password', $defaults);
    }
}
