<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_conversation_id', 'user_id', 'request_id', 'intent', 'structured_filters',
        'tool_calls', 'results_count', 'status', 'response_type', 'error_code', 'fallback_reason', 'knowledge_version', 'latency_ms', 'search_ms', 'tokens_used',
    ];

    protected function casts(): array
    {
        return [
            'structured_filters' => 'array',
            'tool_calls' => 'array',
            'results_count' => 'integer',
            'response_type' => 'string',
            'fallback_reason' => 'string',
            'knowledge_version' => 'string',
            'latency_ms' => 'integer',
            'search_ms' => 'integer',
            'tokens_used' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
