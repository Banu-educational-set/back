<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AttendanceService
{
    public function sessionAttendees(int $sessionId): Builder
    {
        return $this->withPassedAttempt(function (Builder $exam) use ($sessionId) {
            $exam->where('session_id', $sessionId);
        });
    }

    public function courseAttendees(int $courseId): Builder
    {
        return $this->withPassedAttempt(function (Builder $exam) use ($courseId) {
            $exam->whereHas('session', fn (Builder $s) => $s->where('course_id', $courseId));
        });
    }

    public function termAttendees(int $termId): Builder
    {
        return $this->withPassedAttempt(function (Builder $exam) use ($termId) {
            $exam->whereHas('session.course', fn (Builder $c) => $c->where('term_id', $termId));
        });
    }

    /**
     * @param  callable(Builder): void  $examFilter
     */
    private function withPassedAttempt(callable $examFilter): Builder
    {
        return User::query()
            ->with(['roles', 'avatar'])
            ->whereHas('examAttempts', function (Builder $attempt) use ($examFilter) {
                $attempt->where('is_passed', true)
                    ->whereHas('exam', $examFilter);
            })
            ->orderBy('name');
    }
}
