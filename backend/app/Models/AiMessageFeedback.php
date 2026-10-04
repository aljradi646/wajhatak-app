<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessageFeedback extends Model
{
    protected $table = 'ai_message_feedback';

    protected $fillable = [
        'ai_message_id',
        'user_id',
        'feedback',
        'note',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(AiMessage::class, 'ai_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
