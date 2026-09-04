<?php

namespace App\Models;

use App\Enums\ViewingRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViewingRequest extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'client_id', 'agent_id', 'scheduled_date', 'scheduled_time', 'notes', 'status'];

    protected function casts(): array
    {
        return ['scheduled_date' => 'date', 'status' => ViewingRequestStatus::class];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}

