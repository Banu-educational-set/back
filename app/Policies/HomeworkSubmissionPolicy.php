<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\HomeworkSubmission;
use App\Models\User;

class HomeworkSubmissionPolicy
{
    public function review(User $user, HomeworkSubmission $submission): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);
    }
}
