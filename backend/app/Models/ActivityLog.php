<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'ip_address',
        'user_agent',
        'properties',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'subject_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Record an activity event.
     */
    public static function record(
        string $logName,
        string $description,
        mixed $subject = null,
        ?string $ip = null,
        ?string $userAgent = null,
        mixed $properties = null,
        ?int $userId = null,
    ): self {
        return static::create([
            'user_id' => $userId ?? Auth::id(),
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'ip_address' => $ip ?? Request::ip(),
            'user_agent' => $userAgent ?? Request::userAgent(),
            'properties' => $properties ? json_encode($properties) : null,
        ]);
    }
}
