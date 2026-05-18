<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Term;
use App\Models\TermEnrollment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EnrollmentService
{
    public function __construct(private readonly TermCompletionService $completionService) {}

    public function enroll(User $user, Term $term): TermEnrollment
    {
        if (! $term->is_active) {
            throw new RuntimeException('Term is not active.');
        }

        $enrollment = DB::transaction(function () use ($user, $term) {
            $existing = TermEnrollment::query()
                ->where('user_id', $user->id)
                ->where('term_id', $term->id)
                ->first();

            if ($existing) {
                throw new RuntimeException('Already enrolled in this term.');
            }

            return TermEnrollment::create([
                'user_id' => $user->id,
                'term_id' => $term->id,
                'status' => EnrollmentStatus::Active->value,
            ]);
        });

        // Vacuous-completion check: a term with zero exams and zero homework
        // is considered already complete on enrollment.
        $this->completionService->evaluate($user, $term);

        return $enrollment->fresh();
    }

    public function paginateForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return TermEnrollment::query()
            ->with('term')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
