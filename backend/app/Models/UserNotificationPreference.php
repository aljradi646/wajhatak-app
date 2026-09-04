<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'message_notifications',
        'viewing_notifications',
        'property_updates',
    ];

    protected function casts(): array
    {
        return [
            'message_notifications' => 'boolean',
            'viewing_notifications' => 'boolean',
            'property_updates' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
