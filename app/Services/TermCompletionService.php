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
     * Returns true if the user (a) has an accepted submission for every
     * priority homework in the term and (b) has earned a normalized exam
     * grade (out of term.score) >= term.minimum_score.
     */
    public function hasCompletedTerm(User $user, Term $term): bool
    {
        return $this->priorityHomeworksSatisfied($user, $term)
            && $this->studentGradeForTerm($user, $term) >= (int) $term->minimum_score;
    }

    /**
     * The student's normalized grade for the term, on the [0, term.score]
     * scale. Formula: (sum of best-per-exam scores) / (sum of exam max
     * scores) * term.score. Returns term.score when the term has no exams
     * (vacuous max).
     */
    public function studentGradeForTerm(User $user, Term $term): float
    {
        $exams = DB::table('exams')
            ->join('course_sessions', 'course_sessions.id', '=', 'exams.session_id')
            ->join('courses', 'courses.id', '=', 'course_sessions.course_id')
            ->where('courses.term_id', $term->id)
            ->select('exams.id', 'exams.score')
            ->get();

        $sumMax = (int) $exams->sum('score');
        if ($sumMax === 0) {
            return (float) $term->score;
        }

        $examIds = $exams->pluck('id');
        $bestPerExam = DB::table('exam_attempts')
            ->where('user_id', $user->id)
            ->whereIn('exam_id', $examIds)
            ->whereNotNull('submitted_at')
            ->groupBy('exam_id')
            ->select('exam_id', DB::raw('MAX(score) as best_score'))
            ->pluck('best_score', 'exam_id');

        $sumStudent = 0;
        foreach ($examIds as $id) {
            $sumStudent += (int) ($bestPerExam[$id] ?? 0);
        }

        return ($sumStudent / $sumMax) * (int) $term->score;
    }

    private function priorityHomeworksSatisfied(User $user, Term $term): bool
    {
        $homeworkIds = DB::table('homeworks')
            ->join('course_sessions', 'course_sessions.id', '=', 'homeworks.session_id')
            ->join('courses', 'courses.id', '=', 'course_sessions.course_id')
            ->where('courses.term_id', $term->id)
            ->where('homeworks.is_priority', true)
            ->pluck('homeworks.id');

        if ($homeworkIds->isEmpty()) {
            return true;
        }

        $acceptedCount = DB::table('homework_submissions')
            ->where('user_id', $user->id)
            ->whereIn('homework_id', $homeworkIds)
            ->where('status', HomeworkSubmissionStatus::Accepted->value)
            ->distinct()
            ->count('homework_id');

        return $acceptedCount >= $homeworkIds->count();
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
