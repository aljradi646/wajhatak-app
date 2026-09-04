<?php

namespace App\Models;

use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agent_id', 'property_type_id', 'property_location_id', 'title', 'slug', 'reference_code',
        'description', 'transaction_type', 'status', 'price', 'currency', 'area', 'bedrooms',
        'bathrooms', 'parking_spaces', 'is_furnished', 'is_new', 'is_featured', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => TransactionType::class,
            'status' => PropertyStatus::class,
            'price' => 'decimal:2',
            'area' => 'decimal:2',
            'is_furnished' => 'boolean',
            'is_new' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(PropertyLocation::class, 'property_location_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(PropertyFeature::class, 'property_feature');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function viewingRequests(): HasMany
    {
        return $this->hasMany(ViewingRequest::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
