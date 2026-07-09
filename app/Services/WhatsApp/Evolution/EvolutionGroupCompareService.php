<?php

namespace App\Services\WhatsApp\Evolution;

use App\Models\Customer;
use App\Services\WhatsApp\BroadcastWhatsAppMessage;
use App\Support\EvolutionGroupMemberParser;
use App\Support\WapiPhoneNormalizer;
use Illuminate\Support\Collection;

class EvolutionGroupCompareService
{
    public function __construct(
        private EvolutionService $evolutionService
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listWhatsAppGroups(bool $withParticipants = false): array
    {
        $instance = $this->evolutionService->activeInstanceName();
        if ($instance === '') {
            return [];
        }

        $response = $this->evolutionService->clientFor(null, $instance)->fetchAllGroups($instance, $withParticipants);

        return is_array($response) ? $response : [];
    }

    /**
     * @return array{members: array, phone_index: array<string, true>, group_info: array}
     */
    public function loadWhatsAppGroup(string $groupJid): array
    {
        $instance = $this->evolutionService->activeInstanceName();
        if ($instance === '') {
            throw new \RuntimeException('لم يُحدَّد Instance افتراضي لـ Evolution API. عيّن الانستانس من صفحة Evolution API.');
        }

        $client = $this->evolutionService->clientFor(null, $instance);
        $group = $client->findGroupByJid($instance, $groupJid);
        $membersRaw = $client->findGroupMembers($instance, $groupJid);
        $members = EvolutionGroupMemberParser::parse($membersRaw);
        $groupInfo = EvolutionGroupMemberParser::summarizeGroup($group, $groupJid);

        return [
            'members' => $members,
            'phone_index' => $this->buildPhoneIndex($members),
            'group_info' => $groupInfo,
        ];
    }

    /**
     * @return Collection<int, Customer>
     */
    public function getPlatformCustomers(bool $activeOnly = true, bool $requireValidPhone = false): Collection
    {
        $query = Customer::query()
            ->where(function ($q) {
                $q->whereNotNull('phonenumber')->where('phonenumber', '!=', '');
            });

        if ($activeOnly) {
            $query->where('status', 'Active');
        }

        $customers = $query->orderBy('firstname')->orderBy('lastname')->get();

        if ($requireValidPhone) {
            return $customers->filter(fn (Customer $c) => $this->customerPhoneDigits($c) !== null)->values();
        }

        return $customers;
    }

    /**
     * @param  array<string, true>  $phoneIndex
     * @return array{
     *   stats: array<string, int>,
     *   missing: array<int, array<string, mixed>>,
     *   matched: array<int, array<string, mixed>>,
     *   wa_only: array<int, array<string, mixed>>,
     *   no_phone: array<int, array<string, mixed>>
     * }
     */
    public function compareCustomers(Collection $customers, array $phoneIndex, array $waMembers): array
    {
        $missing = [];
        $matched = [];
        $noPhone = [];
        $matchedWaKeys = [];

        foreach ($customers as $customer) {
            $digits = $this->customerPhoneDigits($customer);
            $row = $this->customerRow($customer, $digits);

            if ($digits === null) {
                $noPhone[] = $row;

                continue;
            }

            if ($this->isInWhatsAppGroup($digits, $phoneIndex)) {
                $matched[] = $row;
                foreach ($this->phoneMatchKeys($digits) as $key) {
                    $matchedWaKeys[$key] = true;
                }
            } else {
                $missing[] = $row;
            }
        }

        $waOnly = [];
        foreach ($waMembers as $member) {
            $digits = WapiPhoneNormalizer::normalize($member['phone'] ?? '');
            if ($digits === '') {
                continue;
            }
            $keys = $this->phoneMatchKeys($digits);
            $linkedToPlatform = false;
            foreach ($keys as $key) {
                if (isset($matchedWaKeys[$key])) {
                    $linkedToPlatform = true;
                    break;
                }
            }
            if (! $linkedToPlatform) {
                $waOnly[] = [
                    'phone' => $member['phone'],
                    'phone_jid' => $member['phone_jid'] ?? '',
                    'is_admin' => $member['is_admin'] ?? false,
                    'role' => $member['role'] ?? 'member',
                ];
            }
        }

        return [
            'stats' => [
                'platform_total' => $customers->count(),
                'wa_total' => count($waMembers),
                'matched' => count($matched),
                'missing' => count($missing),
                'wa_only' => count($waOnly),
                'no_phone' => count($noPhone),
            ],
            'missing' => $missing,
            'matched' => $matched,
            'wa_only' => $waOnly,
            'no_phone' => $noPhone,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $members
     * @return array<string, true>
     */
    public function buildPhoneIndex(array $members): array
    {
        $index = [];
        foreach ($members as $member) {
            $digits = WapiPhoneNormalizer::normalize($member['phone'] ?? EvolutionGroupMemberParser::extractPhone($member['phone_jid'] ?? ''));
            if ($digits === '') {
                continue;
            }
            foreach ($this->phoneMatchKeys($digits) as $key) {
                $index[$key] = true;
            }
        }

        return $index;
    }

    public function customerPhoneDigits(Customer $customer): ?string
    {
        $phone = trim((string) ($customer->phonenumber ?? ''));
        if ($phone === '') {
            return null;
        }

        $phone = preg_replace('/\s+/', '', $phone);
        if ($phone !== '' && ! str_starts_with($phone, '+')) {
            $phone = '+'.ltrim($phone, '0');
        }

        $digits = WapiPhoneNormalizer::normalize($phone);

        return WapiPhoneNormalizer::isValidE164Digits($digits) ? $digits : null;
    }

    /**
     * @param  iterable<Customer>  $customers
     * @return array<int, 'in_group'|'not_in_group'|'no_phone'>
     */
    public function waMembershipStatusForCustomers(iterable $customers, array $phoneIndex, BroadcastWhatsAppMessage $broadcast): array
    {
        $status = [];
        foreach ($customers as $customer) {
            $digits = $broadcast->normalizedPhoneDigitsForCustomer($customer);
            if ($digits === null) {
                $status[(int) $customer->id] = 'no_phone';
            } elseif ($this->isInWhatsAppGroup($digits, $phoneIndex)) {
                $status[(int) $customer->id] = 'in_group';
            } else {
                $status[(int) $customer->id] = 'not_in_group';
            }
        }

        return $status;
    }

    /**
     * @param  array<string, true>  $phoneIndex
     */
    public function isInWhatsAppGroup(string $studentDigits, array $phoneIndex): bool
    {
        foreach ($this->phoneMatchKeys($studentDigits) as $key) {
            if (isset($phoneIndex[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function phoneMatchKeys(string $digits): array
    {
        $digits = WapiPhoneNormalizer::normalize($digits);
        if ($digits === '') {
            return [];
        }

        $keys = [$digits];
        foreach ([12, 11, 10, 9] as $len) {
            if (strlen($digits) >= $len) {
                $keys[] = substr($digits, -$len);
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return array<string, mixed>
     */
    private function customerRow(Customer $customer, ?string $digits): array
    {
        $name = trim((string) ($customer->fullname ?: trim(($customer->firstname ?? '').' '.($customer->lastname ?? ''))));

        return [
            'id' => $customer->id,
            'name' => $name !== '' ? $name : '—',
            'email' => $customer->email ?? '',
            'phone' => $digits ?? ($customer->phonenumber ?? '—'),
            'phone_digits' => $digits,
            'phone_display' => $customer->phonenumber ?? '—',
            'groups' => [],
            'courses' => [],
        ];
    }

    public function resolveLabels(?int $courseId, ?int $platformGroupId): array
    {
        return ['course' => null, 'platform_group' => null];
    }
}
