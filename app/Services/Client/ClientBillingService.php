<?php

namespace App\Services\Client;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ClientBillingService
{
    public function hasCustomerProfile(User $user): bool
    {
        return $user->customer()->exists();
    }

    public function customerIdForUser(User $user): ?int
    {
        return $user->customer?->id;
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
