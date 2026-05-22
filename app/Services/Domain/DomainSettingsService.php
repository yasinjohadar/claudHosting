<?php

namespace App\Services\Domain;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class DomainSettingsService
{
    protected const GROUP = 'domains';

    protected const BILLING_CACHE_KEY = 'domain_billing_config';

    /**
     * @return array{renewal_amount: float, invoice_due_days: int}
     */
    public function getBillingConfig(): array
    {
        return Cache::remember(self::BILLING_CACHE_KEY, 300, function () {
            $this->initializeDefaults();

            $keys = config('domains.keys');
            $defaults = config('domains.defaults');
            $stored = SystemSetting::query()
                ->where('group', self::GROUP)
                ->pluck('value', 'key')
                ->toArray();

            return [
                'renewal_amount' => max(0, (float) ($stored[$keys['renewal_amount']] ?? $defaults['renewal_amount'])),
                'invoice_due_days' => max(1, (int) ($stored[$keys['invoice_due_days']] ?? $defaults['invoice_due_days'])),
            ];
        });
    }

    public function updateBillingSettings(array $data): void
    {
        $keys = config('domains.keys');

        if (array_key_exists('renewal_amount', $data)) {
            SystemSetting::set($keys['renewal_amount'], (string) max(0, (float) $data['renewal_amount']), 'string', self::GROUP);
        }

        if (array_key_exists('invoice_due_days', $data)) {
            SystemSetting::set($keys['invoice_due_days'], (string) max(1, (int) $data['invoice_due_days']), 'integer', self::GROUP);
        }

        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget(self::BILLING_CACHE_KEY);
    }

    public function initializeDefaults(): void
    {
        $keys = config('domains.keys');
        $defaults = config('domains.defaults');

        foreach ($keys as $settingKey => $dbKey) {
            if (! SystemSetting::query()->where('group', self::GROUP)->where('key', $dbKey)->exists()) {
                SystemSetting::set($dbKey, (string) ($defaults[$settingKey] ?? ''), 'string', self::GROUP);
            }
        }
    }
}
