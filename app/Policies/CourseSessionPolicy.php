<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\CourseSession;
use App\Models\User;

class CourseSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CourseSession $session): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);
    }

    public function update(User $user, CourseSession $session): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);
    }

    public function delete(User $user, CourseSession $session): bool
    {
        return $user->hasRole(RoleName::Admin->value);
    }
}
