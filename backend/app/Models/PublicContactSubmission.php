<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicContactSubmission extends Model
{
    protected $fillable = [
        'reference', 'name', 'email', 'phone', 'category', 'subject',
        'message', 'status', 'admin_reply', 'replied_by', 'replied_at',
        'email_status', 'email_sent_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
            'email_sent_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }
}
