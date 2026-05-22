<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCoolifyProject extends Model
{
    protected $fillable = [
        'project_uuid',
        'user_id',
        'coolify_team_id',
        'project_name',
    ];

    protected $casts = [
        'coolify_team_id' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
