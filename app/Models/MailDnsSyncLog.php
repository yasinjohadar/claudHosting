<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit trail for every mail-DNS write to Cloudflare.
 *
 * Applies are not transactional and are not rolled back (deleting a record we created
 * would violate "never delete what we did not plan"), so this row is the only way to
 * reconstruct a partially-applied zone afterwards: `meta.records` holds the before,
 * after and result of every record attempted.
 */
class MailDnsSyncLog extends Model
{
    protected $fillable = [
        'user_id',
        'whm_account_id',
        'domain',
        'zone_id',
        'zone_name',
        'source',
        'outcome',
        'created_count',
        'updated_count',
        'failed_count',
        'message',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhmAccount::class, 'whm_account_id');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function record(array $attributes): self
    {
        return static::create(array_merge([
            'user_id' => auth()->id(),
            'source' => 'web',
        ], $attributes));
    }

    public function outcomeLabel(): string
    {
        return match ($this->outcome) {
            'applied' => 'تم التطبيق',
            'partial' => 'تطبيق جزئي',
            'failed' => 'فشل',
            'blocked' => 'مرفوض',
            'dry_run' => 'معاينة فقط',
            default => $this->outcome,
        };
    }
}
