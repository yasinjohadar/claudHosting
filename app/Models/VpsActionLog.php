<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpsActionLog extends Model
{
    protected $fillable = [
        'vps_server_id',
        'user_id',
        'action',
        'success',
        'message',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function vpsServer(): BelongsTo
    {
        return $this->belongsTo(VpsServer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
