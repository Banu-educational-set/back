<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PrerequisiteService
{
    /**
     * A user has "passed" a course when they have a passing attempt on
     * every exam belonging to every session in the course. Courses with
     * zero exams are considered passed.
     */
    public function userHasPassedCourse(User $user, Course $course): bool
    {
        $examIds = DB::table('exams')
            ->join('course_sessions', 'course_sessions.id', '=', 'exams.session_id')
            ->where('course_sessions.course_id', $course->id)
            ->pluck('exams.id');

        if ($examIds->isEmpty()) {
            return true;
        }

        $passed = DB::table('exam_attempts')
            ->where('user_id', $user->id)
            ->whereIn('exam_id', $examIds)
            ->where('is_passed', true)
            ->distinct()
            ->pluck('exam_id');

        return $passed->count() >= $examIds->count();
    }

    /**
     * A user has "passed" a session when they have a passing attempt on
     * every exam belonging to the session. Sessions with zero exams are
     * considered passed.
     */
    public function userHasPassedSession(User $user, CourseSession $session): bool
    {
        $examIds = DB::table('exams')
            ->where('session_id', $session->id)
            ->pluck('id');

        if ($examIds->isEmpty()) {
            return true;
        }

        $passed = DB::table('exam_attempts')
            ->where('user_id', $user->id)
            ->whereIn('exam_id', $examIds)
            ->where('is_passed', true)
            ->distinct()
            ->pluck('exam_id');

        return $passed->count() >= $examIds->count();
    }

    /**
     * Returns the IDs of prerequisite courses the user has NOT yet passed.
     * Missing/null prerequisite list => empty array (no prereqs).
     *
     * @return array<int>
     */
    public function courseUnmetPrerequisites(User $user, Course $course): array
    {
        $ids = $this->normalizeIds($course->prerequisite_course_ids ?? []);
        if ($ids === []) {
            return [];
        }

        $prereqs = Course::query()->whereIn('id', $ids)->get();

        return $prereqs
            ->reject(fn (Course $c) => $this->userHasPassedCourse($user, $c))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Returns the IDs of prerequisite sessions the user has NOT yet passed.
     *
     * @return array<int>
     */
    public function sessionUnmetPrerequisites(User $user, CourseSession $session): array
    {
        $ids = $this->normalizeIds($session->prerequisite_session_ids ?? []);
        if ($ids === []) {
            return [];
        }

        $prereqs = CourseSession::query()->whereIn('id', $ids)->get();

        return $prereqs
            ->reject(fn (CourseSession $s) => $this->userHasPassedSession($user, $s))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Would assigning $candidateIds as the prerequisites of course
     * $courseId create a cycle? Walks the transitive closure of candidate
     * IDs in the existing graph and returns true if $courseId appears.
     *
     * @param  array<int>  $candidateIds
     */
    public function wouldCreateCourseCycle(int $courseId, array $candidateIds): bool
    {
        return $this->reachesNode(
            startIds: $candidateIds,
            target: $courseId,
            table: 'courses',
            column: 'prerequisite_course_ids',
        );
    }

    /**
     * @param  array<int>  $candidateIds
     */
    public function wouldCreateSessionCycle(int $sessionId, array $candidateIds): bool
    {
        return $this->reachesNode(
            startIds: $candidateIds,
            target: $sessionId,
            table: 'course_sessions',
            column: 'prerequisite_session_ids',
        );
    }

    /**
     * @param  array<int>  $startIds
     */
    private function reachesNode(array $startIds, int $target, string $table, string $column): bool
    {
        $visited = [];
        $stack = array_values(array_unique(array_map('intval', $startIds)));

        while ($stack !== []) {
            $current = array_pop($stack);
            if ($current === $target) {
                return true;
            }
            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            $raw = DB::table($table)->where('id', $current)->value($column);
            $next = $this->normalizeIds($this->decode($raw));
            foreach ($next as $id) {
                if (! isset($visited[$id])) {
                    $stack[] = $id;
                }
            }
        }

        return false;
    }

    /**
     * @return array<int>
     */
    private function normalizeIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($v) => is_numeric($v) ? (int) $v : null,
            $value,
        ))));
    }

    private function decode(mixed $raw): mixed
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return $raw;
    }
}
