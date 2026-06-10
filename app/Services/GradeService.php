<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Computes "average of best percentages" school-style averages for a
 * specific student at the session/course/term level.
 *
 *   sessionAverage = mean of (best_score / exam.score * 100) over the
 *                    session's exams. Null when the session has no exams.
 *   courseAverage  = mean of sessionAverages for the course (sessions with
 *                    no exams are excluded). Null when nothing graded.
 *   termAverage    = mean of courseAverages for the term. Null when nothing
 *                    graded.
 */
class GradeService
{
    public function studentSessionAverage(User $user, CourseSession $session): ?float
    {
        return $this->sessionAverageById($user, $session->id);
    }

    public function studentCourseAverage(User $user, Course $course): ?float
    {
        $sessionIds = DB::table('course_sessions')
            ->where('course_id', $course->id)
            ->pluck('id');

        return $this->averageOfAverages(
            $sessionIds->map(fn ($id) => $this->sessionAverageById($user, (int) $id))->all(),
        );
    }

    public function studentTermAverage(User $user, Term $term): ?float
    {
        $courseIds = DB::table('courses')
            ->where('term_id', $term->id)
            ->pluck('id');

        return $this->averageOfAverages(
            $courseIds->map(fn ($id) => $this->courseAverageById($user, (int) $id))->all(),
        );
    }

    private function courseAverageById(User $user, int $courseId): ?float
    {
        $sessionIds = DB::table('course_sessions')
            ->where('course_id', $courseId)
            ->pluck('id');

        return $this->averageOfAverages(
            $sessionIds->map(fn ($id) => $this->sessionAverageById($user, (int) $id))->all(),
        );
    }

    private function sessionAverageById(User $user, int $sessionId): ?float
    {
        $exams = DB::table('exams')
            ->where('session_id', $sessionId)
            ->select('id', 'score')
            ->get();

        if ($exams->isEmpty()) {
            return null;
        }

        $bestPerExam = DB::table('exam_attempts')
            ->where('user_id', $user->id)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->whereNotNull('submitted_at')
            ->groupBy('exam_id')
            ->select('exam_id', DB::raw('MAX(score) as best_score'))
            ->pluck('best_score', 'exam_id');

        $percentages = [];
        foreach ($exams as $exam) {
            $max = (int) $exam->score;
            if ($max <= 0) {
                continue;
            }
            $best = (int) ($bestPerExam[$exam->id] ?? 0);
            $percentages[] = ($best / $max) * 100;
        }

        if ($percentages === []) {
            return null;
        }

        return array_sum($percentages) / count($percentages);
    }

    /**
     * @param  array<int, float|null>  $values
     */
    private function averageOfAverages(array $values): ?float
    {
        $filtered = array_values(array_filter($values, fn ($v) => $v !== null));
        if ($filtered === []) {
            return null;
        }

        return array_sum($filtered) / count($filtered);
    }
}
