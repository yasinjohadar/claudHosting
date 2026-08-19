<?php

namespace App\Services\WhatsApp;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhmAccount;
use App\Support\InternationalPhoneDigits;
use App\Support\WhatsAppTemplateVariables;
use Illuminate\Support\Facades\Log;

/**
 * Renders a WhatsApp message body against a context.
 *
 * Returns the resolved text AND the placeholders it could not resolve, because the caller is
 * the only one who can decide what an unresolved placeholder means. The previous engine
 * simply left unknown placeholders in the string, which meant a single typo — {customer_nmae}
 * — shipped verbatim to a real customer's phone. Here anything unresolved is stripped and
 * reported, so a mistake costs a missing word rather than an embarrassing message.
 */
class WhatsAppTemplateRenderer
{
    /**
     * Every placeholder spelling the codebase has ever written, in the order they are tried.
     * The spaced forms exist because PasswordResetMessageRenderer accepted them.
     *
     * @return list<string>
     */
    private static function patternsFor(string $key): array
    {
        return ['{{'.$key.'}}', '{{ '.$key.' }}', '{'.$key.'}', '{ '.$key.' }'];
    }

    /**
     * Resolve the catalogue against a context.
     *
     * Every entry is a plain string: a template that renders "null" or "Array" into a
     * customer's message is worse than one that renders nothing.
     *
     * @param  array{user?: ?User, customer?: ?Customer, whmAccount?: ?WhmAccount, invoice?: ?Invoice, payment?: ?Payment, extra?: array<string, mixed>}  $context
     * @return array<string, string>
     */
    public function resolve(array $context = []): array
    {
        $user = $context['user'] ?? null;
        // array_key_exists, not ??: a caller that deliberately passes customer => null (because
        // it already tried and failed to load one) must not have the lookup re-run here, outside
        // its own error handling.
        $customer = array_key_exists('customer', $context)
            ? $context['customer']
            : $this->customerOf($user);
        $account = $context['whmAccount'] ?? null;
        $invoice = $context['invoice'] ?? null;
        $payment = $context['payment'] ?? null;

        $values = array_merge(
            $this->customerValues($user, $customer),
            $this->subscriptionValues($account),
            $this->billingValues($invoice, $payment),
            $this->systemValues(),
        );

        // Caller-supplied values win: a code, a reset URL or a one-off amount is known only
        // to the calling flow and must override anything derived here.
        foreach ($this->stringify($context['extra'] ?? []) as $key => $value) {
            $values[$key] = $value;
        }

        return $this->withAliases($values);
    }

    /**
     * @param  array<string, mixed>  $overrides  Values the caller knows and the catalogue does not.
     * @param  array<string, mixed>  $context
     * @return array{text: string, unresolved: list<string>, used: list<string>}
     */
    public function render(string $body, array $context = [], array $overrides = []): array
    {
        $values = $this->resolve($context);

        // Overrides are authoritative and are accepted even when they are not in the
        // catalogue, so callers with their own vocabulary (the password-reset flow) keep
        // working without every one of their keys having to be catalogued.
        foreach ($this->withAliases($this->stringify($overrides)) as $key => $value) {
            $values[$key] = $value;
        }

        $text = $body;
        $used = [];

        foreach ($values as $key => $value) {
            $patterns = self::patternsFor($key);
            $before = $text;
            $text = str_replace($patterns, $value, $text);
            if ($text !== $before) {
                $used[] = $key;
            }
        }

        [$text, $unresolved] = $this->stripUnresolved($text);

        return [
            'text' => WhatsAppMessageTemplate::normalizeBodyForSending($text),
            'unresolved' => $unresolved,
            'used' => array_values(array_unique($used)),
        ];
    }

    /**
     * Render and return only the text, logging anything that could not be resolved.
     *
     * This is the method the send paths use: they need a string, but the failure must not
     * disappear — an unresolved placeholder means the admin's template references data this
     * particular send does not carry, and that is worth a line in the log.
     *
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $overrides
     */
    public function renderText(string $body, array $context = [], array $overrides = [], ?string $source = null): string
    {
        $result = $this->render($body, $context, $overrides);

        if ($result['unresolved'] !== []) {
            Log::channel('whatsapp')->warning('WhatsApp template had unresolved variables; they were removed from the message.', [
                'source' => $source ?? 'unknown',
                'unresolved' => $result['unresolved'],
            ]);
        }

        return $result['text'];
    }

    /**
     * Preview with the catalogue's sample values, for the admin UI.
     *
     * @return array{text: string, unresolved: list<string>, used: list<string>}
     */
    public function preview(string $body): array
    {
        return $this->render($body, [], WhatsAppTemplateVariables::sampleValues());
    }

    /**
     * Placeholders in a body, canonical spelling not required.
     *
     * Used by save-time validation, so it has to see every spelling the renderer supports.
     *
     * @return list<string>
     */
    public static function placeholdersIn(string $body): array
    {
        preg_match_all('/\{\{?\s*([a-zA-Z0-9_]+)\s*\}?\}/', $body, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * Placeholders the catalogue does not know about.
     *
     * @param  list<string>  $additionalKnown  keys a specific template is allowed to use
     * @return list<string>
     */
    public static function unknownPlaceholdersIn(string $body, array $additionalKnown = []): array
    {
        $known = array_merge(WhatsAppTemplateVariables::allKnownKeys(), $additionalKnown);

        return array_values(array_filter(
            self::placeholdersIn($body),
            static fn (string $key): bool => ! in_array($key, $known, true)
        ));
    }

    /**
     * Remove leftover placeholders and report them.
     *
     * @return array{0: string, 1: list<string>}
     */
    private function stripUnresolved(string $text): array
    {
        $unresolved = self::placeholdersIn($text);
        if ($unresolved === []) {
            return [$text, []];
        }

        foreach ($unresolved as $key) {
            $text = str_replace(self::patternsFor($key), '', $text);
        }

        // Removing a placeholder leaves debris: a double space where it sat mid-line, or
        // trailing whitespace where it ended a line. normalizeBodyForSending() only trims
        // bodies that contain HTML, so plain-text templates have to be tidied here.
        $text = preg_replace('/[ \t]{2,}/', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+$/m', '', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return [trim($text), $unresolved];
    }

    /** The user's customer record, or null — never an exception, which would abort the send. */
    private function customerOf(?User $user): ?Customer
    {
        if ($user === null) {
            return null;
        }

        try {
            return $user->customer;
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('Could not load the customer record while rendering a template.', [
                'user_id' => $user->getKey(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    private function customerValues(?User $user, ?Customer $customer): array
    {
        $name = trim((string) ($customer?->fullname ?: trim(($customer?->firstname ?? '').' '.($customer?->lastname ?? ''))));
        if ($name === '') {
            $name = trim((string) ($user?->name ?? ''));
        }

        // Through forUser() on purpose: the phone column holds the national number and the
        // dial code lives in country_code, so reading `phone` alone yields a number that
        // cannot be dialled.
        $phone = $user !== null ? InternationalPhoneDigits::forUser($user) : null;
        if ($phone === null && $customer !== null) {
            $phone = InternationalPhoneDigits::forCustomer($customer);
        }

        return [
            'customer_name' => $name,
            'customer_email' => trim((string) ($customer?->email ?: $user?->email ?? '')),
            'customer_phone' => $phone !== null ? InternationalPhoneDigits::toDisplay($phone) : '',
            'company_name' => trim((string) ($customer?->companyname ?? $user?->companyname ?? '')),
            'customer_city' => trim((string) ($customer?->city ?? $user?->city ?? '')),
            'customer_country' => trim((string) ($customer?->country ?? $user?->country ?? '')),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function subscriptionValues(?WhmAccount $account): array
    {
        if ($account === null) {
            return [];
        }

        $days = $account->subscription_days_remaining;

        return [
            'domain' => trim((string) ($account->domain ?? '')),
            'package' => trim((string) ($account->package ?? '')),
            'cpanel_username' => trim((string) ($account->username ?? '')),
            'subscription_ends_at' => $account->subscription_ends_at?->format('Y-m-d') ?? '',
            'subscription_days_remaining' => $days === null ? '' : (string) $days,
            'subscription_status' => trim((string) ($account->subscription_status_label ?? '')),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function billingValues(?Invoice $invoice, ?Payment $payment): array
    {
        $values = [];

        if ($invoice !== null) {
            $values += [
                'invoice_number' => trim((string) ($invoice->invoice_number ?: $invoice->invoicenum ?? '')),
                'invoice_total' => number_format((float) $invoice->total, 2),
                'invoice_due_date' => $this->formatDate($invoice->duedate),
                'invoice_status' => trim((string) ($invoice->status ?? '')),
                'invoice_url' => $this->invoiceUrl($invoice),
            ];

            // Invoice::$balance is an accessor that SUMs the payments table on every read, so
            // it is neither free nor safe: on an unpersisted invoice the query matches nothing
            // meaningful, and in a broadcast it would fire once per recipient. Only read it
            // for a saved invoice, and never let it break a send.
            $balance = $this->invoiceBalance($invoice);
            if ($balance !== null) {
                $values['invoice_balance'] = number_format($balance, 2);
            }
        }

        if ($payment !== null) {
            $values['payment_amount'] = number_format((float) $payment->amount, 2);
        }

        return $values;
    }

    /**
     * @return array<string, string>
     */
    private function systemValues(): array
    {
        return [
            'app_name' => (string) (config('app.name') ?: 'كلاودسوفت'),
            'login_url' => $this->safeRoute('login'),
            'support_url' => $this->safeRoute('client.tickets.index'),
            'today' => now()->format('Y-m-d'),
            'now' => now()->format('Y-m-d H:i'),
        ];
    }

    /** Null when the balance cannot be established, so the placeholder is reported not faked. */
    private function invoiceBalance(Invoice $invoice): ?float
    {
        if (! $invoice->exists) {
            return null;
        }

        try {
            return (float) $invoice->balance;
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('Could not read invoice balance for a WhatsApp template.', [
                'invoice_id' => $invoice->getKey(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function invoiceUrl(Invoice $invoice): string
    {
        if ($invoice->getKey() === null) {
            return '';
        }

        try {
            return route('client.invoices.show', $invoice);
        } catch (\Throwable) {
            // A template must never take down a send because a route was renamed.
            return '';
        }
    }

    private function safeRoute(string $name): string
    {
        try {
            return route($name);
        } catch (\Throwable) {
            return '';
        }
    }

    private function formatDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        try {
            return \Illuminate\Support\Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    /**
     * Mirror every canonical value onto its aliases, so an older template spelling resolves
     * to the same data instead of being stripped as unknown.
     *
     * @param  array<string, string>  $values
     * @return array<string, string>
     */
    private function withAliases(array $values): array
    {
        foreach (WhatsAppTemplateVariables::definitions() as $key => $definition) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            foreach ($definition['aliases'] as $alias) {
                // An explicitly supplied alias keeps its own value.
                if (! array_key_exists($alias, $values)) {
                    $values[$alias] = $values[$key];
                }
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    private function stringify(array $values): array
    {
        $clean = [];
        foreach ($values as $key => $value) {
            if (! is_string($key) || is_array($value) || is_object($value)) {
                continue;
            }

            $clean[$key] = $value === null ? '' : (string) $value;
        }

        return $clean;
    }
}
