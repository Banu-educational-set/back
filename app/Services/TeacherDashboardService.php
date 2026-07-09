<?php

namespace App\Services;

use App\Enums\HomeworkSubmissionStatus;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\TermEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TeacherDashboardService
{
    private const RECENT_LIMIT = 5;

    /**
     * @return array<string, mixed>
     */
    public function build(User $teacher): array
    {
        $id = $teacher->id;

        // Terms that contain at least one of the teacher's courses — used to
        // count the distinct students the teacher effectively teaches.
        $termIds = Course::where('teacher_id', $id)->pluck('term_id')->unique()->values();

        $teachesCourse = fn (Builder $q) => $q->where('teacher_id', $id);

        return [
            'profile' => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'avatar_url' => $teacher->loadMissing('avatar')->avatar?->url(),
                'province' => $teacher->loadMissing('province')->province?->name,
                'city' => $teacher->loadMissing('city')->city?->name,
                'role' => $teacher->getRoleNames()->first(),
                'membership_days' => (int) $teacher->created_at?->diffInDays(now()),
            ],
            'stats' => [
                'courses_count' => Course::where('teacher_id', $id)->count(),
                'sessions_count' => CourseSession::whereHas('course', $teachesCourse)->count(),
                'students_count' => $termIds->isEmpty() ? 0 : TermEnrollment::whereIn('term_id', $termIds)
                    ->distinct('user_id')->count('user_id'),
                'exams_count' => Exam::whereHas('session.course', $teachesCourse)->count(),
                'homeworks_count' => Homework::whereHas('session.course', $teachesCourse)->count(),
                'pending_reviews' => HomeworkSubmission::where('status', HomeworkSubmissionStatus::Pending->value)
                    ->whereHas('homework.session.course', $teachesCourse)->count(),
            ],
            'recent_submissions' => $this->recentSubmissions($id),
            'recent_courses' => $this->recentCourses($id),
        ];
    }

    /**
     * Latest homework submissions awaiting the teacher's review.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentSubmissions(int $teacherId): array
    {
        return HomeworkSubmission::query()
            ->where('status', HomeworkSubmissionStatus::Pending->value)
            ->whereHas('homework.session.course', fn (Builder $q) => $q->where('teacher_id', $teacherId))
            ->with(['user:id,name', 'homework:id,title'])
            ->orderByDesc('id')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->map(fn (HomeworkSubmission $s) => [
                'id' => $s->id,
                'student_id' => $s->user_id,
                'student_name' => $s->user?->name,
                'homework_id' => $s->homework_id,
                'homework_title' => $s->homework?->title,
                'status' => $s->status instanceof \BackedEnum ? $s->status->value : $s->status,
                'submitted_at' => $s->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentCourses(int $teacherId): array
    {
        return Course::query()
            ->where('teacher_id', $teacherId)
            ->with('term:id,title')
            ->orderByDesc('id')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->map(fn (Course $c) => [
                'id' => $c->id,
                'title' => $c->title,
                'term_id' => $c->term_id,
                'term_title' => $c->term?->title,
            ])
            ->all();
    }
}
