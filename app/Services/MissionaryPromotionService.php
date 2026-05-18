<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleName;
use App\Models\TermEnrollment;
use App\Models\User;

class MissionaryPromotionService
{
    public function evaluate(User $user): bool
    {
        if ($user->hasRole(RoleName::Missionary->value)) {
            return false;
        }

        $completedTerms = TermEnrollment::query()
            ->where('user_id', $user->id)
            ->where('status', EnrollmentStatus::Completed->value)
            ->count();

        $threshold = (int) config('education.terms_required_for_missionary');

        if ($completedTerms < $threshold) {
            return false;
        }

        $user->assignRole(RoleName::Missionary->value);

        return true;
    }
}
