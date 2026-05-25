<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContainerFileAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'coolify_wordpress_site_id',
        'user_id',
        'action',
        'path',
        'success',
        'ip',
        'detail',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CoolifyWordpressSite::class, 'coolify_wordpress_site_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
