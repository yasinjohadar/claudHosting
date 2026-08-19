<?php

namespace Tests\Unit\WhatsApp;

use App\Http\Controllers\Admin\CustomerWhatsAppController;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageTemplate;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

/**
 * The per-customer send action from the customers list.
 *
 * Exercised through the controller rather than HTTP: RefreshDatabase is unusable here (four
 * unrelated migrations query MySQL information_schema and abort on sqlite), so only the tables
 * this path reads are built.
 */
class CustomerWhatsAppSendTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            '2026_01_31_100000_create_whatsapp_message_templates_table.php',
            '2026_08_19_100000_add_management_fields_to_whatsapp_message_templates_table.php',
            '0001_01_01_000000_create_users_table.php',
            '2026_05_31_120000_add_country_code_to_users_table.php',
            '2026_05_31_140000_add_address_fields_to_users_table.php',
            // forUser() falls back to the customer record when a user has no phone.
            '2023_12_16_000001_create_customers_table.php',
            '2026_03_01_150000_add_user_id_to_customers_table.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
    }

    protected function tearDown(): void
    {
        foreach (['whatsapp_message_templates', 'customers', 'users', 'password_reset_tokens', 'sessions'] as $table) {
            Schema::dropIfExists($table);
        }

        Mockery::close();
        parent::tearDown();
    }

    private function controller(): CustomerWhatsAppController
    {
        return app(CustomerWhatsAppController::class);
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
            'city' => 'إستنبول',
        ], $attributes));
        $user->password = 'irrelevant';
        $user->save();

        return $user;
    }

    private function template(array $attributes = []): WhatsAppMessageTemplate
    {
        return WhatsAppMessageTemplate::create(array_merge([
            'name' => 'ترحيب',
            'slug' => 'welcome_x',
            'body' => 'مرحباً {customer_name} من {customer_city}',
            'type' => WhatsAppMessageTemplate::TYPE_TEXT,
            'category' => 'general',
            'is_active' => true,
        ], $attributes));
    }

    /** @var list<array{to: string, text: string}> */
    private array $sent = [];

    /** Records what the controller asked to send instead of hitting the network. */
    private function spySender(): SendWhatsAppMessage
    {
        $this->sent = [];

        $mock = Mockery::mock(SendWhatsAppMessage::class);
        $mock->shouldReceive('sendTextSync')
            ->andReturnUsing(function (string $to, string $text, bool $previewUrl = false): WhatsAppMessage {
                $this->sent[] = ['to' => $to, 'text' => $text];

                return new WhatsAppMessage;
            });

        app()->instance(SendWhatsAppMessage::class, $mock);

        return $mock;
    }

    public function test_the_preview_uses_the_customers_real_data(): void
    {
        $user = $this->customer();
        $template = $this->template();

        $response = $this->controller()->preview($this->request(['template_id' => $template->id]), $user);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        // Real values, not the catalogue's sample ones — the admin is about to message this person.
        $this->assertSame('مرحباً أسامة عداس من إستنبول', $data['text']);
        $this->assertSame('+905519665883', $data['recipient']);
        $this->assertNull($data['warning']);
    }

    public function test_the_preview_names_variables_this_customer_has_no_data_for(): void
    {
        $user = $this->customer();
        $template = $this->template(['body' => 'نطاقك {domain} ينتهي {subscription_ends_at}']);

        $data = $this->controller()->preview($this->request(['template_id' => $template->id]), $user)->getData(true);

        // Surfaced before sending, so a gap in the sentence is a choice rather than a surprise.
        $this->assertSame(['domain', 'subscription_ends_at'], $data['unresolved']);
        $this->assertStringContainsString('{domain}', $data['warning']);
    }

    public function test_sending_a_template_delivers_the_rendered_text(): void
    {
        $sender = $this->spySender();
        $user = $this->customer();
        $template = $this->template();

        $data = $this->controller()
            ->send($this->request(['template_id' => $template->id]), $user, $sender)
            ->getData(true);

        $this->assertTrue($data['success']);
        $this->assertCount(1, $this->sent);
        $this->assertSame('+905519665883', $this->sent[0]['to']);
        $this->assertSame('مرحباً أسامة عداس من إستنبول', $this->sent[0]['text']);
    }

    public function test_free_text_is_rendered_with_variables_too(): void
    {
        $sender = $this->spySender();
        $user = $this->customer();

        $this->controller()->send($this->request(['message' => 'أهلاً {customer_name}']), $user, $sender);

        $this->assertSame('أهلاً أسامة عداس', $this->sent[0]['text']);
    }

    public function test_a_customer_without_a_dialable_number_is_refused(): void
    {
        $sender = $this->spySender();
        $template = $this->template();

        // No phone at all, and a phone too short to be E.164 — the two cases the guard exists
        // for. (A phone with no country_code is NOT one of them: that is treated as an already
        // international number, which is the documented behaviour for legacy rows.)
        foreach ([['phone' => null], ['phone' => '12']] as $index => $attributes) {
            $user = $this->customer($attributes + ['email' => 'nophone'.$index.'@example.com']);

            $response = $this->controller()->send($this->request(['template_id' => $template->id]), $user, $sender);

            $this->assertSame(422, $response->getStatusCode(), 'should refuse for: '.json_encode($attributes));
            $this->assertStringContainsString('رمز الدولة', $response->getData(true)['message']);
        }

        $this->assertSame([], $this->sent, 'nothing may be sent to an unusable number');
    }

    public function test_the_row_button_is_disabled_for_the_same_numbers_the_send_refuses(): void
    {
        // The list decides whether to enable the button with the same resolver the controller
        // guards with, so a green button can never lead to a 422.
        $this->assertNull(\App\Support\InternationalPhoneDigits::forUser($this->customer(['phone' => null])));
        $this->assertNull(\App\Support\InternationalPhoneDigits::forUser(
            $this->customer(['phone' => '12', 'email' => 'short@example.com'])
        ));
        $this->assertNotNull(\App\Support\InternationalPhoneDigits::forUser(
            $this->customer(['email' => 'ok@example.com'])
        ));
    }

    public function test_an_inactive_template_is_refused(): void
    {
        $sender = $this->spySender();
        $user = $this->customer();
        $template = $this->template(['is_active' => false]);

        $response = $this->controller()->send($this->request(['template_id' => $template->id]), $user, $sender);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([], $this->sent);
    }

    public function test_an_empty_payload_is_refused(): void
    {
        $sender = $this->spySender();
        $user = $this->customer();

        $response = $this->controller()->send($this->request([]), $user, $sender);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('اختر قالباً', $response->getData(true)['message']);
        $this->assertSame([], $this->sent);
    }

    public function test_a_template_that_renders_to_nothing_is_not_sent(): void
    {
        $sender = $this->spySender();
        $user = $this->customer();
        // Every variable is unresolvable for this customer, so the body collapses to empty.
        $template = $this->template(['body' => '{domain}']);

        $response = $this->controller()->send($this->request(['template_id' => $template->id]), $user, $sender);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([], $this->sent, 'a blank message must never reach a customer');
    }

    public function test_an_over_long_free_text_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->controller()->send(
            $this->request(['message' => str_repeat('ا', 4097)]),
            $this->customer(),
            $this->spySender()
        );
    }

    public function test_the_template_list_only_offers_active_text_templates(): void
    {
        $this->template(['name' => 'مفعّل', 'slug' => 'a_on', 'is_active' => true]);
        $this->template(['name' => 'معطّل', 'slug' => 'a_off', 'is_active' => false]);

        $data = $this->controller()->templates()->getData(true);

        $names = array_column($data['templates'], 'name');
        $this->assertContains('مفعّل', $names);
        $this->assertNotContains('معطّل', $names);
    }
}
