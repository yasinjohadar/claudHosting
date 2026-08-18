<?php

namespace App\Services\Whm\MailDns;

use App\Models\MailDnsSyncLog;
use Illuminate\Support\Facades\Log;

/**
 * Thin seam around the audit-log write.
 *
 * Kept separate from WhmMailDnsSyncService so the service stays unit-testable without a
 * database, and so a logging failure can never abort an apply that already wrote real
 * DNS records — losing the audit row is bad, but leaving the caller believing nothing
 * happened would be worse.
 */
class MailDnsSyncLogger
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return int|null the log id, or null if the row could not be written
     */
    public function record(array $attributes): ?int
    {
        try {
            return MailDnsSyncLog::record($attributes)->id;
        } catch (\Throwable $e) {
            Log::error('Failed to write mail DNS sync log', [
                'domain' => $attributes['domain'] ?? null,
                'outcome' => $attributes['outcome'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
