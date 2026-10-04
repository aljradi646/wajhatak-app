<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_conversation_id', 'role', 'content', 'structured_filters', 'property_ids', 'status', 'response_type', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'structured_filters' => 'array',
            'property_ids' => 'array',
            'response_type' => 'string',
            'metadata' => 'array',
            'content' => 'string',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }
}
