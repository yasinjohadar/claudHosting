<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppContact extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_contacts';

    protected $fillable = [
        'wa_id',
        'name',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    /**
     * Relationship with messages
     */
    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'contact_id');
    }

    /**
     * Find or create contact by WhatsApp ID
     */
    public static function findOrCreateByWaId(string $waId, ?string $name = null): self
    {
        return static::firstOrCreate(
            ['wa_id' => $waId],
            ['name' => $name]
        );
    }

    /**
     * Update last seen timestamp
     */
    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
