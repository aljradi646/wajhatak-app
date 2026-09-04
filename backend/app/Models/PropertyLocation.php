<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'region_id',
        'city_id',
        'area_id',
        'city',
        'district',
        'neighborhood',
        'address',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function cityReference(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
