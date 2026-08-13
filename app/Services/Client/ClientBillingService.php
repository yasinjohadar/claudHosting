<?php

namespace App\Services\Client;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ClientBillingService
{
    public function hasCustomerProfile(User $user): bool
    {
        return $this->ensureCustomerProfile($user) !== null;
    }

    public function customerIdForUser(User $user): ?int
    {
        return $this->ensureCustomerProfile($user)?->id;
    }

    /**
     * يضمن وجود سجل Customer مربوط بالمستخدم (ربط بالبريد أو إنشاء محلي).
     */
    public function ensureCustomerProfile(User $user): ?Customer
    {
        $existing = $user->customer;
        if ($existing) {
            return $existing;
        }

        $byEmail = Customer::query()
            ->where('email', $user->email)
            ->whereNull('user_id')
            ->first();

        if ($byEmail) {
            $byEmail->update(['user_id' => $user->id]);
            $user->unsetRelation('customer');

            return $byEmail->fresh();
        }

        $nameParts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];
        $firstname = $nameParts[0] ?? ($user->name ?: 'عميل');
        $lastname = $nameParts[1] ?? '';

        $customer = Customer::create([
            'user_id' => $user->id,
            'whmcs_id' => null,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'fullname' => $user->name,
            'email' => $user->email,
            'companyname' => $user->companyname,
            'address1' => $user->address1,
            'address2' => $user->address2,
            'city' => $user->city,
            'state' => $user->state,
            'postcode' => $user->postcode,
            'country' => $user->country ?: config('whmcs.default_country', 'SY'),
            'phonenumber' => trim(($user->country_code ? $user->country_code.' ' : '').($user->phone ?? '')),
            'status' => 'Active',
            'date_created' => now(),
        ]);

        $user->unsetRelation('customer');

        return $customer;
    }

    /**
     * @return LengthAwarePaginator<Invoice>|Collection<int, Invoice>
     */
    public function invoicesForUser(User $user, int $perPage = 15): LengthAwarePaginator|Collection
    {
        $customerId = $this->customerIdForUser($user);

        if ($customerId === null) {
            return collect();
        }

        return Invoice::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function userCanViewInvoice(User $user, Invoice $invoice): bool
    {
        $customerId = $this->customerIdForUser($user);

        return $customerId !== null && (int) $invoice->customer_id === $customerId;
    }
}
