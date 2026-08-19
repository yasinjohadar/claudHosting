<?php

namespace Tests\Unit\WhatsApp;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\WhmAccount;
use App\Services\WhatsApp\WhatsAppTemplateRenderer;
use App\Support\WhatsAppTemplateVariables;
use Tests\TestCase;

/**
 * The renderer carries the whole correctness story of the templating feature, so it is
 * tested against arrays and unsaved models — no database, no network.
 *
 * The regression that matters most: the previous engine left unknown placeholders in the
 * string, so one typo ({customer_nmae}) was delivered verbatim to a customer's phone.
 */
class WhatsAppTemplateRendererTest extends TestCase
{
    private function renderer(): WhatsAppTemplateRenderer
    {
        return new WhatsAppTemplateRenderer;
    }

    private function user(): User
    {
        return new User([
            'name' => 'أسامة عداس',
            'email' => 'eng.osama@example.com',
            'phone' => '5519665883',
            'country_code' => '+90',
            'city' => 'إستنبول',
            'country' => 'TR',
        ]);
    }

    public function test_both_brace_styles_and_their_spaced_forms_resolve(): void
    {
        $body = 'A {customer_name} B {{customer_name}} C { customer_name } D {{ customer_name }}';

        $result = $this->renderer()->render($body, ['user' => $this->user()]);

        $this->assertSame('A أسامة عداس B أسامة عداس C أسامة عداس D أسامة عداس', $result['text']);
        $this->assertSame([], $result['unresolved']);
    }

    public function test_an_unknown_placeholder_is_stripped_and_reported(): void
    {
        $result = $this->renderer()->render(
            'مرحباً {customer_nmae} أهلاً بك',
            ['user' => $this->user()]
        );

        $this->assertStringNotContainsString('{', $result['text'], 'no placeholder may survive into a sent message');
        $this->assertStringNotContainsString('customer_nmae', $result['text']);
        $this->assertSame(['customer_nmae'], $result['unresolved']);
    }

    public function test_stripping_does_not_leave_double_spaces(): void
    {
        $result = $this->renderer()->render('مرحباً {nope} بك', ['user' => $this->user()]);

        $this->assertSame('مرحباً بك', $result['text']);
    }

    public function test_the_legacy_aliases_still_resolve(): void
    {
        // Templates and the password-reset body in production were written against these.
        $body = '{student_name} / {user_name} / {student_email} / {email} / {phone}';

        $result = $this->renderer()->render($body, ['user' => $this->user()]);

        $this->assertSame(
            'أسامة عداس / أسامة عداس / eng.osama@example.com / eng.osama@example.com / +905519665883',
            $result['text']
        );
        $this->assertSame([], $result['unresolved']);
    }

    public function test_the_phone_variable_carries_the_dial_code(): void
    {
        // Reading $user->phone alone would render 5519665883, a number nobody can call.
        $result = $this->renderer()->render('{customer_phone}', ['user' => $this->user()]);

        $this->assertSame('+905519665883', $result['text']);
    }

    public function test_subscription_variables_come_from_the_whm_account(): void
    {
        $account = new WhmAccount([
            'domain' => 'example.com',
            'package' => 'Business',
            'username' => 'examplec',
            'subscription_ends_at' => now()->addDays(14),
        ]);

        $result = $this->renderer()->render(
            '{domain} | {package} | {cpanel_username} | {subscription_ends_at} | {subscription_days_remaining}',
            ['whmAccount' => $account]
        );

        $this->assertStringContainsString('example.com', $result['text']);
        $this->assertStringContainsString('Business', $result['text']);
        $this->assertStringContainsString('examplec', $result['text']);
        $this->assertStringContainsString(now()->addDays(14)->format('Y-m-d'), $result['text']);
        $this->assertStringContainsString('14', $result['text']);
        $this->assertSame([], $result['unresolved']);
    }

    public function test_billing_variables_are_formatted_as_money_and_dates(): void
    {
        $invoice = new Invoice([
            'invoice_number' => 'INV-2026-0142',
            'total' => 450,
            'duedate' => '2026-09-01',
            'status' => 'Unpaid',
        ]);
        $payment = new Payment(['amount' => 300]);

        $result = $this->renderer()->render(
            '{invoice_number} | {invoice_total} | {invoice_due_date} | {payment_amount}',
            ['invoice' => $invoice, 'payment' => $payment]
        );

        $this->assertSame('INV-2026-0142 | 450.00 | 2026-09-01 | 300.00', $result['text']);
    }

    public function test_the_balance_variable_does_not_query_for_an_unsaved_invoice(): void
    {
        // Invoice::$balance SUMs the payments table on every read. Reading it for an invoice
        // that was never saved queries on a null id, and in a broadcast it would fire once per
        // recipient — so it is reported as unresolved rather than guessed at.
        $result = $this->renderer()->render(
            'المتبقي {invoice_balance}',
            ['invoice' => new Invoice(['invoice_number' => 'INV-1', 'total' => 450])]
        );

        $this->assertSame('المتبقي', $result['text']);
        $this->assertSame(['invoice_balance'], $result['unresolved']);
    }

    public function test_a_missing_context_reports_instead_of_throwing(): void
    {
        // A template that mentions an invoice, sent from a flow that has no invoice.
        $result = $this->renderer()->render('فاتورتك {invoice_number} جاهزة', ['user' => $this->user()]);

        $this->assertSame('فاتورتك جاهزة', $result['text']);
        $this->assertSame(['invoice_number'], $result['unresolved']);
    }

    public function test_caller_overrides_win_over_the_catalogue(): void
    {
        $result = $this->renderer()->render(
            'رمزك {code} يا {customer_name}',
            ['user' => $this->user()],
            ['code' => '482913', 'customer_name' => 'اسم مخصص']
        );

        $this->assertSame('رمزك 482913 يا اسم مخصص', $result['text']);
    }

    public function test_an_override_outside_the_catalogue_is_still_honoured(): void
    {
        // The password-reset flow has its own vocabulary and passes it explicitly.
        $result = $this->renderer()->render(
            'صالح {expire_minutes} دقيقة — {reset_url}',
            [],
            ['expire_minutes' => '30', 'reset_url' => 'https://example.com/reset/abc']
        );

        $this->assertSame('صالح 30 دقيقة — https://example.com/reset/abc', $result['text']);
        $this->assertSame([], $result['unresolved']);
    }

    public function test_html_stored_by_an_older_editor_becomes_whatsapp_formatting(): void
    {
        $result = $this->renderer()->render('<p>مرحباً <strong>{customer_name}</strong></p>', ['user' => $this->user()]);

        $this->assertSame('مرحباً *أسامة عداس*', $result['text']);
    }

    public function test_a_customer_without_a_user_still_resolves(): void
    {
        $customer = new Customer([
            'firstname' => 'أسامة',
            'lastname' => 'عداس',
            'email' => 'c@example.com',
            'companyname' => 'كلاودسوفت',
            'city' => 'دمشق',
        ]);

        $result = $this->renderer()->render(
            '{customer_name} | {customer_email} | {company_name} | {customer_city}',
            ['customer' => $customer]
        );

        $this->assertSame('أسامة عداس | c@example.com | كلاودسوفت | دمشق', $result['text']);
    }

    public function test_preview_fills_every_catalogue_variable(): void
    {
        $body = implode(' ', array_map(
            static fn (string $key): string => '{'.$key.'}',
            WhatsAppTemplateVariables::keys()
        ));

        $result = $this->renderer()->preview($body);

        $this->assertSame([], $result['unresolved'], 'the preview must resolve every variable the UI offers');
        $this->assertStringNotContainsString('{', $result['text']);
    }

    public function test_placeholder_extraction_sees_every_spelling(): void
    {
        $found = WhatsAppTemplateRenderer::placeholdersIn('{a} {{b}} { c } {{ d }} plain text');

        $this->assertSame(['a', 'b', 'c', 'd'], $found);
    }

    public function test_unknown_placeholder_detection_backs_save_time_validation(): void
    {
        $this->assertSame(
            ['customer_nmae'],
            WhatsAppTemplateRenderer::unknownPlaceholdersIn('{customer_name} {customer_nmae}')
        );

        // A flow may whitelist its own extra keys.
        $this->assertSame(
            [],
            WhatsAppTemplateRenderer::unknownPlaceholdersIn('{reset_url}', ['reset_url'])
        );
    }

    public function test_array_and_object_overrides_are_ignored_not_rendered(): void
    {
        // "Array" or "Object" appearing in a customer's message is worse than nothing.
        $result = $this->renderer()->render('{customer_name}', [], ['customer_name' => ['a', 'b']]);

        $this->assertStringNotContainsString('Array', $result['text']);
    }

    public function test_one_template_produces_a_different_message_per_recipient(): void
    {
        // The point of the whole feature: a broadcast must personalise per number. Rendering
        // once for the batch would send the first recipient's name and city to everyone.
        $template = new \App\Models\WhatsAppMessageTemplate([
            'name' => 'ترحيب',
            'slug' => 'welcome_x',
            'body' => 'مرحباً {customer_name} من {customer_city} — جوالك {customer_phone}',
        ]);

        $osama = new User(['name' => 'أسامة عداس', 'phone' => '5519665883', 'country_code' => '+90', 'city' => 'إستنبول']);
        $yassin = new User(['name' => 'ياسين جوخدار', 'phone' => '944123456', 'country_code' => '+963', 'city' => 'دمشق']);

        $first = $template->render([], ['user' => $osama]);
        $second = $template->render([], ['user' => $yassin]);

        $this->assertSame('مرحباً أسامة عداس من إستنبول — جوالك +905519665883', $first);
        $this->assertSame('مرحباً ياسين جوخدار من دمشق — جوالك +963944123456', $second);
        $this->assertNotSame($first, $second);
    }

    public function test_render_detailed_reports_what_a_send_could_not_resolve(): void
    {
        $template = new \App\Models\WhatsAppMessageTemplate([
            'name' => 'فاتورة',
            'slug' => 'invoice_x',
            'body' => 'فاتورتك {invoice_number} بمبلغ {invoice_total}',
        ]);

        $result = $template->renderDetailed([], ['user' => $this->user()]);

        $this->assertSame('فاتورتك بمبلغ', $result['text']);
        $this->assertSame(['invoice_number', 'invoice_total'], $result['unresolved']);
    }
}
