<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIMessage extends Model
{
    use HasFactory;

    protected $table = 'ai_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tokens_used',
        'prompt_tokens',
        'completion_tokens',
        'cost',
        'response_time',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'cost' => 'float',
        'response_time' => 'integer',
    ];

    /**
     * Get the conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AIConversation::class, 'conversation_id');
    }
}
