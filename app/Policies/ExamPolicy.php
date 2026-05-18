<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Exam;
use App\Models\TermEnrollment;
use App\Models\User;

class ExamPolicy
{
    public function view(User $user, Exam $exam): bool
    {
        if ($user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value])) {
            return true;
        }

        $termId = $exam->session?->course?->term_id;
        if (! $termId) {
            return false;
        }

        return TermEnrollment::query()
            ->where('user_id', $user->id)
            ->where('term_id', $termId)
            ->exists();
    }

    public function submit(User $user, Exam $exam): bool
    {
        if (! $user->hasAnyRole([RoleName::Student->value, RoleName::Missionary->value])) {
            return false;
        }

        return $this->view($user, $exam);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);
    }

    public function update(User $user, Exam $exam): bool
    {
        return $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $user->hasRole(RoleName::Admin->value);
    }
}
