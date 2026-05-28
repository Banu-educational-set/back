<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\HomeworkSubmissionStatus;
use App\Models\Term;
use App\Models\TermEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TermCompletionService
{
    public function __construct(private readonly MissionaryPromotionService $promotionService) {}

    /**
     * Returns true if every exam in the term has a passing attempt by the user
     * AND every homework has an accepted submission.
     */
    public function hasCompletedTerm(User $user, Term $term): bool
    {
        $termId = $term->id;

        // Exam IDs scoped to this term via session -> course -> term
        $examIds = DB::table('exams')
            ->join('course_sessions', 'course_sessions.id', '=', 'exams.session_id')
            ->join('courses', 'courses.id', '=', 'course_sessions.course_id')
            ->where('courses.term_id', $termId)
            ->pluck('exams.id');

        if ($examIds->isNotEmpty()) {
            $passedExams = DB::table('exam_attempts')
                ->where('user_id', $user->id)
                ->whereIn('exam_id', $examIds)
                ->where('is_passed', true)
                ->distinct()
                ->pluck('exam_id');

            if ($passedExams->count() < $examIds->count()) {
                return false;
            }
        }

        // Only priority homeworks block term completion; non-priority ones are optional.
        $homeworkIds = DB::table('homeworks')
            ->join('course_sessions', 'course_sessions.id', '=', 'homeworks.session_id')
            ->join('courses', 'courses.id', '=', 'course_sessions.course_id')
            ->where('courses.term_id', $termId)
            ->where('homeworks.is_priority', true)
            ->pluck('homeworks.id');

        if ($homeworkIds->isNotEmpty()) {
            $acceptedSubmissions = DB::table('homework_submissions')
                ->where('user_id', $user->id)
                ->whereIn('homework_id', $homeworkIds)
                ->where('status', HomeworkSubmissionStatus::Accepted->value)
                ->distinct()
                ->pluck('homework_id');

            if ($acceptedSubmissions->count() < $homeworkIds->count()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Marks the user's enrollment in the term as passed if all conditions are met.
     * Triggers missionary promotion afterwards.
     */
    public function evaluate(User $user, Term $term): ?TermEnrollment
    {
        if (! $this->hasCompletedTerm($user, $term)) {
            return null;
        }

        $enrollment = TermEnrollment::query()
            ->where('user_id', $user->id)
            ->where('term_id', $term->id)
            ->first();

        if (! $enrollment) {
            return null;
        }

        if ($enrollment->status === EnrollmentStatus::Completed) {
            return $enrollment;
        }

        $enrollment->update([
            'status' => EnrollmentStatus::Completed->value,
            'completed_at' => now(),
        ]);

        $this->promotionService->evaluate($user);

        return $enrollment->fresh();
    }
}
