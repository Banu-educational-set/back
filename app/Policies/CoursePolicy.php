<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Course $course): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);
    }

    public function update(User $user, Course $course): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->hasRole(RoleName::Admin->value);
    }
}
