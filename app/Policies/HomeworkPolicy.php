<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Homework;
use App\Models\User;

class HomeworkPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Homework $homework): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);
    }

    public function update(User $user, Homework $homework): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);
    }

    public function delete(User $user, Homework $homework): bool
    {
        return $user->hasRole(RoleName::Admin->value);
    }
}
