<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\TermEnrollment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Read-only views of the content a missionary has "passed" — i.e. everything
 * that belongs to a term whose enrollment is completed. Mirrors the student
 * term/course/session shapes but scoped to passed (completed) terms only.
 */
class MissionaryPassedService
{
    public function __construct(
        private readonly EnrollmentService $enrollmentService,
        private readonly CourseService $courseService,
        private readonly CourseSessionService $sessionService,
    ) {}

    /**
     * The user's completed-term enrollments (same shape as /student/my-terms).
     */
    public function terms(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->enrollmentService->paginateForUser(
            $user,
            EnrollmentStatus::Completed->value,
            $perPage,
        );
    }

    /**
     * Courses belonging to the user's passed terms (same shape as /courses).
     */
    public function courses(User $user, ?int $termId, int $perPage = 20): LengthAwarePaginator
    {
        $termIds = $this->passedTermIds($user);

        $paginator = Course::query()
            ->with(['term', 'teacher.avatar', 'cover'])
            ->withCount(['sessions', 'sessionExams as exams_count', 'sessionHomeworks as homeworks_count'])
            ->whereIn('term_id', $termIds)
            ->when($termId, fn ($q, $id) => $q->where('term_id', $id))
            ->orderByDesc('id')
            ->paginate($perPage);

        $this->courseService->attachPrerequisiteCourses($paginator->getCollection());

        return $paginator;
    }

    /**
     * Sessions belonging to the user's passed terms (same shape as /sessions).
     */
    public function sessions(User $user, ?int $courseId, int $perPage = 20): LengthAwarePaginator
    {
        $termIds = $this->passedTermIds($user);

        $paginator = CourseSession::query()
            ->with('course.term')
            ->withCount(['exams', 'homeworks'])
            ->whereHas('course', fn ($c) => $c->whereIn('term_id', $termIds))
            ->when($courseId, fn ($q, $id) => $q->where('course_id', $id))
            ->orderByDesc('id')
            ->paginate($perPage);

        $this->sessionService->attachPrerequisiteSessions($paginator->getCollection());

        return $paginator;
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function passedTermIds(User $user)
    {
        return TermEnrollment::query()
            ->where('user_id', $user->id)
            ->where('status', EnrollmentStatus::Completed->value)
            ->pluck('term_id');
    }
}
