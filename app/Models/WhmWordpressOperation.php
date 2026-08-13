<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhmWordpressOperation extends Model
{
    public const STATUSES = ['queued', 'running', 'completed', 'failed'];

    protected $fillable = [
        'whm_wordpress_site_id',
        'user_id',
        'job_id',
        'action',
        'action_label',
        'params',
        'status',
        'success',
        'message',
        'output',
        'result_file_path',
        'result_file_size',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'params' => 'array',
        'success' => 'boolean',
        'result_file_size' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(WhmWordpressSite::class, 'whm_wordpress_site_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasDownloadableFile(): bool
    {
        return filled($this->result_file_path);
    }
}
