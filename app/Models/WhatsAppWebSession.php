<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WhatsAppWebSession extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_web_sessions';

    protected $fillable = [
        'session_id',
        'phone_number',
        'name',
        'status',
        'qr_code',
        'connected_at',
        'disconnected_at',
        'settings',
        'error_message',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'settings' => 'array',
    ];

    /**
     * Check if session is connected
     */
    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    /**
     * Check if session is connecting
     */
    public function isConnecting(): bool
    {
        return $this->status === 'connecting';
    }

    /**
     * Check if session is disconnected
     */
    public function isDisconnected(): bool
    {
        return $this->status === 'disconnected';
    }

    /**
     * Mark session as connected
     */
    public function markAsConnected(string $phoneNumber, string $name): void
    {
        $this->update([
            'status' => 'connected',
            'phone_number' => $phoneNumber,
            'name' => $name,
            'connected_at' => now(),
            'qr_code' => null,
            'error_message' => null,
        ]);
    }

    /**
     * Mark session as disconnected
     */
    public function markAsDisconnected(?string $errorMessage = null): void
    {
        $this->update([
            'status' => $errorMessage ? 'error' : 'disconnected',
            'disconnected_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Update QR code
     */
    public function updateQrCode(string $qrCode): void
    {
        $this->update([
            'qr_code' => $qrCode,
            'status' => 'connecting',
        ]);
    }
}
