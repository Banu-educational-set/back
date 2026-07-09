<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\ExamAttempt;
use App\Models\TermEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudentDashboardService
{
    /**
     * Build the aggregated student dashboard payload.
     *
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $attendedSessionIds = $this->attendedSessionIds($user);

        return [
            'profile' => $this->profile($user),
            'stats' => [
                'hours_spent' => $this->hoursSpent($attendedSessionIds),
                'enrolled_terms' => TermEnrollment::where('user_id', $user->id)->count(),
                'attended_sessions' => count($attendedSessionIds),
                'completed_courses' => $this->courseCountForStatus($user, EnrollmentStatus::Completed),
                'active_courses' => $this->courseCountForStatus($user, EnrollmentStatus::Active),
            ],
            'last_enrolled_course' => $this->lastEnrolledCourse($user),
            'last_watched_session' => $this->lastWatchedSession($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(User $user): array
    {
        $user->loadMissing(['avatar', 'province', 'city']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $user->avatar?->url(),
            'province' => $user->province?->name,
            'city' => $user->city?->name,
            // diffInDays returns a positive count of whole days since sign-up.
            'membership_days' => (int) $user->created_at?->diffInDays(now()),
        ];
    }

    /**
     * Distinct session ids the student has "attended" — i.e. passed the
     * session's exam. Mirrors AttendanceService's passed-attempt definition.
     *
     * @return array<int, int>
     */
    private function attendedSessionIds(User $user): array
    {
        return ExamAttempt::query()
            ->where('exam_attempts.user_id', $user->id)
            ->where('exam_attempts.is_passed', true)
            ->join('exams', 'exams.id', '=', 'exam_attempts.exam_id')
            ->whereNotNull('exams.session_id')
            ->distinct()
            ->pluck('exams.session_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Sum of duration_minutes over attended sessions (config default when a
     * session has no explicit duration), expressed in whole hours.
     *
     * @param  array<int, int>  $sessionIds
     */
    private function hoursSpent(array $sessionIds): int
    {
        if ($sessionIds === []) {
            return 0;
        }

        $default = (int) config('education.default_session_duration_minutes', 60);

        $minutes = CourseSession::whereIn('id', $sessionIds)
            ->get(['duration_minutes'])
            ->sum(fn (CourseSession $s) => $s->duration_minutes ?: $default);

        return (int) round($minutes / 60);
    }

    private function courseCountForStatus(User $user, EnrollmentStatus $status): int
    {
        $termIds = TermEnrollment::query()
            ->where('user_id', $user->id)
            ->where('status', $status->value)
            ->pluck('term_id');

        if ($termIds->isEmpty()) {
            return 0;
        }

        return Course::whereIn('term_id', $termIds)->count();
    }

    /**
     * The student's most recent enrollment, surfaced as one course of that
     * term (its first course) with the course's session date range + teacher.
     *
     * @return array<string, mixed>|null
     */
    private function lastEnrolledCourse(User $user): ?array
    {
        $enrollment = TermEnrollment::where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        if (! $enrollment) {
            return null;
        }

        $course = Course::with('teacher')
            ->where('term_id', $enrollment->term_id)
            ->orderBy('id')
            ->first();

        if (! $course) {
            return null;
        }

        $range = CourseSession::where('course_id', $course->id)
            ->selectRaw('MIN(starts_at) as starts_at, MAX(starts_at) as ends_at')
            ->first();

        return [
            'course_id' => $course->id,
            'title' => $course->title,
            'starts_at' => $this->iso($range?->starts_at),
            'ends_at' => $this->iso($range?->ends_at),
            'teacher' => $course->teacher ? [
                'id' => $course->teacher->id,
                'name' => $course->teacher->name,
                'avatar_url' => $course->teacher->avatar?->url(),
            ] : null,
        ];
    }

    /**
     * The session of the student's most recently passed exam.
     *
     * @return array<string, mixed>|null
     */
    private function lastWatchedSession(User $user): ?array
    {
        $attempt = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('is_passed', true)
            ->with(['exam.session.course.teacher'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->first();

        $session = $attempt?->exam?->session;
        if (! $session) {
            return null;
        }

        $course = $session->course;
        $teacher = $course?->teacher;

        return [
            'session_id' => $session->id,
            'title' => $session->title,
            'session_number' => $this->sessionNumber($session),
            'course_title' => $course?->title,
            'teacher' => $teacher ? [
                'id' => $teacher->id,
                'name' => $teacher->name,
            ] : null,
            'watched_at' => $this->iso($attempt->submitted_at),
        ];
    }

    /**
     * 1-based position of a session within its course when ordered by
     * starts_at then id (there is no stored session number/order column).
     */
    private function sessionNumber(CourseSession $session): int
    {
        return CourseSession::where('course_id', $session->course_id)
            ->orderByRaw('starts_at is null, starts_at asc')
            ->orderBy('id')
            ->pluck('id')
            ->search($session->id) + 1;
    }

    private function iso($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof \DateTimeInterface
            ? \Illuminate\Support\Carbon::instance($value)->toIso8601String()
            : \Illuminate\Support\Carbon::parse($value)->toIso8601String();
    }
}
