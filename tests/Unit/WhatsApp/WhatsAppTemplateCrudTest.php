<?php

namespace Tests\Unit\WhatsApp;

use App\Http\Controllers\Admin\WhatsAppMessageTemplateController;
use App\Models\WhatsAppMessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Save-time rules for the template form.
 *
 * The controller is exercised directly rather than over HTTP: RefreshDatabase is unusable in
 * this project (four unrelated migrations query MySQL information_schema and abort on sqlite),
 * so only the tables this feature touches are built.
 */
class WhatsAppTemplateCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            '2026_01_31_100000_create_whatsapp_message_templates_table.php',
            '2026_08_19_100000_add_management_fields_to_whatsapp_message_templates_table.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('whatsapp_message_templates');

        parent::tearDown();
    }

    private function controller(): WhatsAppMessageTemplateController
    {
        return app(WhatsAppMessageTemplateController::class);
    }

    private function request(array $data): Request
    {
        return Request::create('/admin/whatsapp-templates', 'POST', $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'ترحيب بحساب جديد',
            'description' => 'يُرسل بعد التجهيز',
            'slug' => '',
            'body' => 'مرحباً {customer_name}، نطاقك {domain} جاهز.',
            'category' => 'subscription',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_a_valid_template_is_created_and_its_variables_recorded(): void
    {
        $this->controller()->store($this->request($this->payload()));

        $template = WhatsAppMessageTemplate::first();

        $this->assertNotNull($template);
        $this->assertSame('subscription', $template->category);
        $this->assertTrue($template->is_active);
        // Recorded from the body, so the list can never drift from what the template uses.
        $this->assertSame(['customer_name', 'domain'], $template->variables);
        $this->assertFalse($template->is_system, 'templates made in the UI must never be protected');
    }

    public function test_a_slug_is_generated_when_left_blank(): void
    {
        // Str::slug() yields nothing for Arabic, so a stable fallback is required — an empty
        // slug would collide with the next blank-slug template.
        $this->controller()->store($this->request($this->payload()));

        $slug = WhatsAppMessageTemplate::first()->slug;
        $this->assertNotSame('', (string) $slug);
        $this->assertNotNull($slug);

        $this->controller()->store($this->request($this->payload(['name' => 'قالب آخر'])));

        $slugs = WhatsAppMessageTemplate::pluck('slug')->all();
        $this->assertCount(2, array_unique($slugs), 'generated slugs must stay unique');
    }

    public function test_an_unknown_variable_is_rejected_and_named(): void
    {
        try {
            $this->controller()->store($this->request($this->payload([
                'body' => 'مرحباً {customer_nmae}',
            ])));
            $this->fail('a typo in a variable must not be saveable');
        } catch (ValidationException $e) {
            $message = $e->validator->errors()->first('body');
            // Naming the offending key is the whole point: "invalid template" would leave the
            // admin hunting through the body.
            $this->assertStringContainsString('{customer_nmae}', $message);
        }

        $this->assertSame(0, WhatsAppMessageTemplate::count());
    }

    public function test_the_dead_course_variable_is_rejected_too(): void
    {
        $this->expectException(ValidationException::class);

        $this->controller()->store($this->request($this->payload(['body' => 'دورة {course_name}'])));
    }

    public function test_legacy_variable_spellings_are_still_accepted(): void
    {
        $this->controller()->store($this->request($this->payload([
            'body' => 'مرحباً {student_name}، بريدك {email}',
        ])));

        $this->assertSame(1, WhatsAppMessageTemplate::count(), 'older spellings must not break saving');
    }

    public function test_a_body_over_the_whatsapp_limit_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        // 4096 is WhatsApp's own cap; accepting more would fail at the API after the admin
        // believed the template was saved and working.
        $this->controller()->store($this->request($this->payload(['body' => str_repeat('ا', 4097)])));
    }

    public function test_a_duplicate_slug_is_rejected(): void
    {
        $this->controller()->store($this->request($this->payload(['slug' => 'welcome_x'])));

        $this->expectException(ValidationException::class);
        $this->controller()->store($this->request($this->payload(['slug' => 'welcome_x', 'name' => 'آخر'])));
    }

    public function test_the_otp_template_cannot_lose_its_code_variable(): void
    {
        $otp = WhatsAppMessageTemplate::create([
            'name' => 'رمز التحقق',
            'slug' => WhatsAppMessageTemplate::SLUG_OTP,
            'body' => 'رمزك {code}',
            'type' => WhatsAppMessageTemplate::TYPE_TEXT,
            'category' => 'auth',
            'is_active' => true,
            'is_system' => true,
        ]);

        try {
            $this->controller()->update(
                $this->request($this->payload(['body' => 'رمز التحقق الخاص بك جاهز', 'category' => 'auth'])),
                $otp
            );
            $this->fail('an OTP template with no {code} would deliver a message with no code');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('{code}', $e->validator->errors()->first('body'));
        }

        $this->assertSame('رمزك {code}', $otp->fresh()->body);
    }

    public function test_a_system_template_keeps_its_slug_when_edited(): void
    {
        $system = WhatsAppMessageTemplate::create([
            'name' => 'إشعار دفعة',
            'slug' => WhatsAppMessageTemplate::SLUG_PAYMENT_RECEIVED,
            'body' => 'دفعتك {payment_amount}',
            'type' => WhatsAppMessageTemplate::TYPE_TEXT,
            'category' => 'billing',
            'is_active' => true,
            'is_system' => true,
        ]);

        $this->controller()->update(
            $this->request($this->payload([
                'slug' => 'renamed_by_admin',
                'body' => 'تم استلام {payment_amount} — شكراً',
                'category' => 'billing',
            ])),
            $system
        );

        $fresh = $system->fresh();
        // The slug is the contract with the listener; renaming it would silently stop the
        // payment notification with no error anywhere.
        $this->assertSame(WhatsAppMessageTemplate::SLUG_PAYMENT_RECEIVED, $fresh->slug);
        $this->assertSame('تم استلام {payment_amount} — شكراً', $fresh->body, 'the wording must still be editable');
    }

    public function test_a_system_template_cannot_be_deleted(): void
    {
        $system = WhatsAppMessageTemplate::create([
            'name' => 'إشعار دفعة',
            'slug' => WhatsAppMessageTemplate::SLUG_PAYMENT_RECEIVED,
            'body' => 'دفعتك {payment_amount}',
            'type' => WhatsAppMessageTemplate::TYPE_TEXT,
            'category' => 'billing',
            'is_active' => true,
            'is_system' => true,
        ]);

        $this->controller()->destroy($system);

        $this->assertNotNull($system->fresh(), 'deleting it would stop payment notifications silently');
    }

    public function test_an_ordinary_template_can_be_renamed_and_deleted(): void
    {
        $this->controller()->store($this->request($this->payload(['slug' => 'welcome_x'])));
        $template = WhatsAppMessageTemplate::first();

        $this->controller()->update(
            $this->request($this->payload(['slug' => 'welcome_renamed'])),
            $template
        );
        $this->assertSame('welcome_renamed', $template->fresh()->slug);

        $this->controller()->destroy($template->fresh());
        $this->assertNull(WhatsAppMessageTemplate::find($template->id));
    }

    public function test_a_protected_slug_is_guarded_even_without_the_system_flag(): void
    {
        // A row seeded or imported without is_system must still not be deletable.
        $mis = WhatsAppMessageTemplate::create([
            'name' => 'OTP',
            'slug' => WhatsAppMessageTemplate::SLUG_OTP,
            'body' => 'رمزك {code}',
            'type' => WhatsAppMessageTemplate::TYPE_TEXT,
            'category' => 'auth',
            'is_active' => true,
            'is_system' => false,
        ]);

        $this->assertTrue($mis->isProtected());

        $this->controller()->destroy($mis);
        $this->assertNotNull($mis->fresh());
    }

    public function test_render_strips_an_unknown_variable_that_bypassed_the_form(): void
    {
        // Seeders, imports and raw SQL never pass through validation, so the send path has to
        // defend itself too.
        $template = WhatsAppMessageTemplate::create([
            'name' => 'مستورد',
            'slug' => 'imported',
            'body' => 'مرحباً {customer_name} {mystery_key}',
            'type' => WhatsAppMessageTemplate::TYPE_TEXT,
            'category' => 'general',
            'is_active' => true,
        ]);

        $text = $template->render(['customer_name' => 'أسامة']);

        $this->assertSame('مرحباً أسامة', $text);
        $this->assertStringNotContainsString('{', $text);
    }
}
