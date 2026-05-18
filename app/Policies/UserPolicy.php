<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleName::Admin->value);
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasRole(RoleName::Admin->value) || $user->id === $target->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Admin->value);
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasRole(RoleName::Admin->value);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->hasRole(RoleName::Admin->value) && $user->id !== $target->id;
    }
}
