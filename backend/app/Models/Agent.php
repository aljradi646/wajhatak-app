<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'license_number', 'bio', 'rating', 'reviews_count', 'is_active',
        'agency_name', 'job_title', 'phone', 'whatsapp', 'city', 'national_id',
        'id_document_path', 'license_document_path', 'experience_years', 'address',
        'website', 'facebook', 'instagram', 'twitter', 'photo_path',
        'rejection_reason', 'verification_status', 'verified_at', 'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:2',
            'is_active' => 'boolean',
            'verified_at' => 'datetime',
            'experience_years' => 'integer',
        ];
    }

    /** الوكيل موثق ومعتمد من الإدارة؟ (بوابة النشر) */
    public function isApproved(): bool
    {
        return $this->verification_status === 'approved' && $this->is_active;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function viewingRequests(): HasMany
    {
        return $this->hasMany(ViewingRequest::class);
    }
}
