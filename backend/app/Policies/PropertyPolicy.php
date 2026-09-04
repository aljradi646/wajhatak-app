<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function view(?User $user, Property $property): bool
    {
        return $property->status->value === 'published'
            || ($user && ($user->hasRole('admin') || $property->agent->user_id === $user->id));
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['agent', 'admin']) && $user->is_active;
    }

    public function update(User $user, Property $property): bool
    {
        return $user->hasRole('admin') || $property->agent->user_id === $user->id;
    }

    public function delete(User $user, Property $property): bool
    {
        return $this->update($user, $property);
    }
}
