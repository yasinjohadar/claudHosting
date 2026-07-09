<?php

namespace App\Services\WhatsApp;

use App\Models\Customer;
use App\Models\User;
use App\Support\WapiPhoneNormalizer;
use Illuminate\Support\Collection;

class BroadcastWhatsAppMessage
{
    public function __construct(
        protected SendWhatsAppMessage $sendService
    ) {}

    public function getStudentsByCriteria(?int $courseId = null, ?int $groupId = null): Collection
    {
        return collect();
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

    public function replacePlaceholders(string $template, User $student, $course = null, $group = null): string
    {
        return $this->replaceCustomerPlaceholders(
            $template,
            new Customer([
                'fullname' => $student->name,
                'email' => $student->email,
            ])
        );
    }

    public function replaceCustomerPlaceholders(string $template, Customer $customer): string
    {
        $name = trim((string) ($customer->fullname ?: trim(($customer->firstname ?? '').' '.($customer->lastname ?? ''))));

        $replacements = [
            '{student_name}' => $name !== '' ? $name : 'عميل',
            '{customer_name}' => $name !== '' ? $name : 'عميل',
            '{student_email}' => $customer->email ?? '',
            '{customer_email}' => $customer->email ?? '',
            '{course_name}' => '',
            '{group_name}' => '',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
}
