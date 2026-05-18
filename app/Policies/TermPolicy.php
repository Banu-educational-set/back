<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Term;
use App\Models\User;

class TermPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Term $term): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);
    }

    public function update(User $user, Term $term): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);
    }

    public function delete(User $user, Term $term): bool
    {
        return $user->hasRole(RoleName::Admin->value);
    }
}
