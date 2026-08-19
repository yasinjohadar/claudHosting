<?php

namespace Tests\Unit\WhatsApp;

use App\Http\Controllers\Admin\CustomerPasswordController;
use App\Models\User;
use App\Models\WhatsAppMessageTemplate;
use App\Services\Auth\PasswordCredentialDeliveryService;
use App\Services\Auth\PasswordResetMessageRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

/**
 * Setting a customer's password from the customers list, and delivering the credentials.
 *
 * The behaviours worth locking: the password is never changed when a requested channel cannot
 * work, an unticked channel is genuinely not used, and a delivery failure after the password
 * has already changed is reported as a partial success rather than a plain failure.
 */
class CustomerPasswordTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            '2026_01_13_142821_create_system_settings_table.php',
            '2026_01_31_100000_create_whatsapp_message_templates_table.php',
            '2026_08_19_100000_add_management_fields_to_whatsapp_message_templates_table.php',
            '0001_01_01_000000_create_users_table.php',
            '2026_05_31_120000_add_country_code_to_users_table.php',
            '2026_05_31_140000_add_address_fields_to_users_table.php',
            '2023_12_16_000001_create_customers_table.php',
            '2026_03_01_150000_add_user_id_to_customers_table.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
    }

    protected function tearDown(): void
    {
        foreach ([
            'whatsapp_message_templates', 'customers', 'users',
            'password_reset_tokens', 'sessions', 'system_settings',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Mockery::close();
        parent::tearDown();
    }

    private function controller(): CustomerPasswordController
    {
        return app(CustomerPasswordController::class);
    }

    private function request(array $data): Request
    {
        return Request::create('/x', 'POST', $data);
    }

    private function customer(array $attributes = []): User
    {
        $user = new User(array_merge([
            'name' => 'أسامة عداس',
            'email' => 'osama@example.com',
            'phone' => '5519665883',
            'country_code' => '+90',
        ], $attributes));
        $user->password = Hash::make('old-password');
        $user->save();

        return $user;
    }

    public function test_it_offers_three_distinct_strong_suggestions(): void
    {
        $data = $this->controller()->suggest(app(PasswordCredentialDeliveryService::class))->getData(true);

        $this->assertCount(3, $data['passwords']);
        $this->assertCount(3, array_unique($data['passwords']), 'suggestions must not repeat');

        foreach ($data['passwords'] as $password) {
            $this->assertGreaterThanOrEqual(12, strlen($password));
            $this->assertMatchesRegularExpression('/[a-z]/', $password);
            $this->assertMatchesRegularExpression('/[A-Z]/', $password);
            $this->assertMatchesRegularExpression('/\d/', $password);
            $this->assertMatchesRegularExpression('/[^A-Za-z0-9]/', $password);
        }
    }

    public function test_it_sets_the_password_without_sending_anything_by_default(): void
    {
        $user = $this->customer();
        $delivery = Mockery::mock(PasswordCredentialDeliveryService::class);
        $delivery->shouldNotReceive('deliver');

        $data = $this->controller()->update(
            $this->request(['password' => 'Str0ng!Pass99', 'password_confirmation' => 'Str0ng!Pass99']),
            $user,
            $delivery
        )->getData(true);

        $this->assertTrue($data['success']);
        $this->assertTrue(Hash::check('Str0ng!Pass99', $user->fresh()->password));
        $this->assertStringContainsString('لم تُرسل البيانات', $data['message']);
    }

    public function test_a_disabled_channel_is_genuinely_not_attempted(): void
    {
        // The behaviour that made the new flags necessary: deliver() used to attempt BOTH
        // channels regardless, so an unticked "email" box was ignored and the customer still
        // got the mail. Asserted at the service, where the guard lives.
        Mail::fake();

        $result = app(PasswordCredentialDeliveryService::class)->deliver(
            $this->customer(),
            'Str0ng!Pass99',
            PasswordCredentialDeliveryService::CONTEXT_ADMIN_RESET,
            viaEmail: false,
            viaWhatsApp: false,
        );

        Mail::assertNothingSent();
        $this->assertFalse($result['email_sent']);
        $this->assertFalse($result['whatsapp_sent']);
    }

    public function test_the_controller_passes_the_unticked_channel_through(): void
    {
        $user = $this->customer();

        $delivery = Mockery::mock(PasswordCredentialDeliveryService::class);
        $delivery->shouldReceive('deliver')
            ->once()
            ->andReturn(['whatsapp_sent' => true, 'email_sent' => false]);

        $data = $this->controller()->update(
            $this->request([
                'password' => 'Str0ng!Pass99',
                'password_confirmation' => 'Str0ng!Pass99',
                'notify_whatsapp' => true,
                'notify_email' => false,
            ]),
            $user,
            $delivery
        )->getData(true);

        $this->assertTrue($data['whatsapp_sent']);
        $this->assertFalse($data['email_sent']);
        $this->assertStringContainsString('واتساب', $data['message']);
        $this->assertStringNotContainsString('البريد', $data['message']);
    }

    public function test_it_refuses_before_changing_the_password_when_whatsapp_cannot_work(): void
    {
        $user = $this->customer(['phone' => null]);
        $originalHash = $user->password;

        $delivery = Mockery::mock(PasswordCredentialDeliveryService::class);
        $delivery->shouldNotReceive('deliver');

        $response = $this->controller()->update(
            $this->request([
                'password' => 'Str0ng!Pass99',
                'password_confirmation' => 'Str0ng!Pass99',
                'notify_whatsapp' => true,
            ]),
            $user,
            $delivery
        );

        $this->assertSame(422, $response->getStatusCode());
        // Changing it first would leave a password nobody can tell the customer about.
        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_a_delivery_failure_after_the_change_is_reported_as_partial_success(): void
    {
        $user = $this->customer();

        $delivery = Mockery::mock(PasswordCredentialDeliveryService::class);
        $delivery->shouldReceive('deliver')
            ->once()
            ->andThrow(new \InvalidArgumentException('تعذّر إرسال بيانات الدخول عبر الواتساب.'));

        $data = $this->controller()->update(
            $this->request([
                'password' => 'Str0ng!Pass99',
                'password_confirmation' => 'Str0ng!Pass99',
                'notify_whatsapp' => true,
            ]),
            $user,
            $delivery
        )->getData(true);

        $this->assertTrue($data['success'], 'the password did change, so this is not a failure');
        $this->assertTrue(Hash::check('Str0ng!Pass99', $user->fresh()->password));
        $this->assertNotNull($data['delivery_error']);
        // An admin who reads "failed" would set the password again for nothing.
        $this->assertStringContainsString('كلمة المرور تغيّرت فعلاً', $data['message']);
    }

    public function test_a_mismatched_confirmation_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->controller()->update(
            $this->request(['password' => 'Str0ng!Pass99', 'password_confirmation' => 'different']),
            $this->customer(),
            Mockery::mock(PasswordCredentialDeliveryService::class)
        );
    }

    public function test_a_short_password_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->controller()->update(
            $this->request(['password' => 'short', 'password_confirmation' => 'short']),
            $this->customer(),
            Mockery::mock(PasswordCredentialDeliveryService::class)
        );
    }

    public function test_the_preview_contains_the_password_and_names_the_template(): void
    {
        $user = $this->customer();
        WhatsAppMessageTemplate::create([
            'name' => 'بيانات الدخول للعميل',
            'slug' => WhatsAppMessageTemplate::SLUG_CREDENTIALS,
            'body' => 'مرحباً {customer_name}، كلمة مرورك: {password}',
            'type' => WhatsAppMessageTemplate::TYPE_TEXT,
            'category' => 'auth',
            'is_active' => true,
            'is_system' => true,
        ]);

        $data = $this->controller()
            ->preview($this->request(['password' => 'Str0ng!Pass99']), $user, app(PasswordResetMessageRenderer::class))
            ->getData(true);

        $this->assertSame('مرحباً أسامة عداس، كلمة مرورك: Str0ng!Pass99', $data['text']);
        $this->assertSame('+905519665883', $data['recipient']);
        $this->assertSame('بيانات الدخول للعميل', $data['template']);
    }

    public function test_the_credentials_template_is_used_when_active(): void
    {
        $user = $this->customer();
        WhatsAppMessageTemplate::create([
            'name' => 'بيانات الدخول للعميل',
            'slug' => WhatsAppMessageTemplate::SLUG_CREDENTIALS,
            'body' => 'نص مخصص من القالب — {password}',
            'type' => WhatsAppMessageTemplate::TYPE_TEXT,
            'category' => 'auth',
            'is_active' => true,
            'is_system' => true,
        ]);

        $rendered = app(PasswordResetMessageRenderer::class)->renderCredentialWhatsApp($user, 'Str0ng!Pass99');

        $this->assertSame('نص مخصص من القالب — Str0ng!Pass99', $rendered);
    }

    public function test_a_deactivated_credentials_template_falls_back_to_the_built_in_wording(): void
    {
        $user = $this->customer();
        WhatsAppMessageTemplate::create([
            'name' => 'بيانات الدخول للعميل',
            'slug' => WhatsAppMessageTemplate::SLUG_CREDENTIALS,
            'body' => 'نص معطّل',
            'type' => WhatsAppMessageTemplate::TYPE_TEXT,
            'category' => 'auth',
            'is_active' => false,
            'is_system' => true,
        ]);

        $rendered = app(PasswordResetMessageRenderer::class)->renderCredentialWhatsApp($user, 'Str0ng!Pass99');

        // Deactivating a template must not silence the message the customer needs.
        $this->assertStringNotContainsString('نص معطّل', $rendered);
        $this->assertStringContainsString('Str0ng!Pass99', $rendered);
    }
}
