<?php

namespace App\Services\WhatsApp;

use App\Models\Customer;
use App\Models\User;
use App\Support\InternationalPhoneDigits;
use App\Support\WapiPhoneNormalizer;
use Illuminate\Support\Collection;

class BroadcastWhatsAppMessage
{
    public function __construct(
        protected SendWhatsAppMessage $sendService
    ) {}

    /**
     * Recipients a broadcast would reach: every user whose stored phone yields a dialable
     * number.
     *
     * This used to `return collect()` unconditionally — a stub left from a training-courses
     * app — so the broadcast feature always answered "لا يوجد مستخدمون لديهم أرقام هواتف
     * صحيحة" no matter how many customers had phones.
     */
    public function getBroadcastRecipients(): Collection
    {
        return User::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get()
            // Through forUser() so country_code is combined with the national number; reading
            // `phone` alone would hand the provider a number that cannot be dialled.
            ->filter(fn (User $user): bool => InternationalPhoneDigits::forUser($user) !== null)
            ->values();
    }

    /**
     * @deprecated Kept for the two existing call sites; the course/group arguments were never
     *             used by this application. Prefer getBroadcastRecipients().
     */
    public function getStudentsByCriteria(?int $courseId = null, ?int $groupId = null): Collection
    {
        return $this->getBroadcastRecipients();
    }

    public function getCustomersByCriteria(bool $activeOnly = true): Collection
    {
        $query = Customer::query()
            ->whereNotNull('phonenumber')
            ->where('phonenumber', '!=', '');

        if ($activeOnly) {
            $query->where('status', 'Active');
        }

        return $query->get()->filter(function (Customer $customer) {
            return $this->normalizedPhoneDigitsForCustomer($customer) !== null;
        })->values();
    }

    public function normalizedPhoneDigitsForCustomer(Customer $customer): ?string
    {
        $phone = trim((string) ($customer->phonenumber ?? ''));
        if ($phone === '') {
            return null;
        }

        if (! str_starts_with($phone, '+')) {
            $phone = '+'.ltrim($phone, '0');
        }

        $normalized = WapiPhoneNormalizer::normalize($phone);

        return WapiPhoneNormalizer::isValidE164Digits($normalized) ? $normalized : null;
    }

    public function normalizedPhoneDigitsForWapi(User $user): ?string
    {
        $phone = trim((string) ($user->phone ?? ''));
        if ($phone === '') {
            return null;
        }

        if (! str_starts_with($phone, '+')) {
            $phone = '+'.ltrim($phone, '0');
        }

        $normalized = WapiPhoneNormalizer::normalize($phone);

        return WapiPhoneNormalizer::isValidE164Digits($normalized) ? $normalized : null;
    }

    /**
     * Personalise a message for one recipient.
     *
     * Delegates to WhatsAppTemplateRenderer, which reaches the whole variable catalogue. The
     * previous implementation rebuilt the User as a bare Customer carrying only a name and an
     * email, so every other variable — phone, company, city, country — was unreachable no
     * matter what the admin typed.
     */
    public function replacePlaceholders(string $template, User $student, $course = null, $group = null): string
    {
        return app(WhatsAppTemplateRenderer::class)->renderText(
            $template,
            ['user' => $student],
            $this->nameFallback($this->displayName($student->customer, (string) $student->name)),
            'broadcast:user:'.$student->getKey(),
        );
    }

    public function replaceCustomerPlaceholders(string $template, Customer $customer): string
    {
        return app(WhatsAppTemplateRenderer::class)->renderText(
            $template,
            ['customer' => $customer],
            $this->nameFallback($this->displayName($customer)),
            'broadcast:customer:'.$customer->getKey(),
        );
    }

    private function displayName(?Customer $customer, string $userName = ''): string
    {
        $name = trim((string) ($customer?->fullname ?: trim(($customer?->firstname ?? '').' '.($customer?->lastname ?? ''))));

        return $name !== '' ? $name : trim($userName);
    }

    /**
     * Keep the long-standing "عميل" stand-in for a nameless recipient.
     *
     * Only for broadcasts, where it already applied — a message opening with "مرحباً ،" reads
     * as broken, while a global default would override the wording other flows chose.
     *
     * @return array<string, string>
     */
    private function nameFallback(string $name): array
    {
        return $name === '' ? ['customer_name' => 'عميل'] : [];
    }
}
