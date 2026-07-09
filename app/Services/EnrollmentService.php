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
        if (! $term->isOpenNow()) {
            throw new RuntimeException(__('errors.term_not_open_enrollment'));
        }

        $enrollment = DB::transaction(function () use ($user, $term) {
            $existing = TermEnrollment::query()
                ->where('user_id', $user->id)
                ->where('term_id', $term->id)
                ->first();

            if ($existing) {
                throw new RuntimeException(__('errors.already_enrolled'));
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

    public function paginateForUser(User $user, ?string $status, int $perPage = 20): LengthAwarePaginator
    {
        return TermEnrollment::query()
            ->with(['term' => fn ($q) => $q
                ->withCount(['courses', 'enrollments', 'termSessions as sessions_count'])
                ->withCount([
                    'termSessions as exams_count' => fn ($qq) => $qq->join('exams', 'exams.session_id', '=', 'course_sessions.id')->select(DB::raw('count(distinct exams.id)')),
                    'termSessions as homeworks_count' => fn ($qq) => $qq->join('homeworks', 'homeworks.session_id', '=', 'course_sessions.id')->select(DB::raw('count(distinct homeworks.id)')),
                ])
            ])
            ->where('user_id', $user->id)
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function paginateForTerm(int $termId, ?string $status, int $perPage = 20): LengthAwarePaginator
    {
        return TermEnrollment::query()
            ->with(['user.roles', 'user.avatar'])
            ->where('term_id', $termId)
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
