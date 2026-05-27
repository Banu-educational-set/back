<?php

namespace App\Services;

use App\Models\Homework;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class HomeworkService
{
    public function paginate(?int $sessionId, ?int $courseId, int $perPage = 20): LengthAwarePaginator
    {
        return Homework::query()
            ->with('session.course')
            ->when($sessionId, fn ($q, $id) => $q->where('session_id', $id))
            ->when($courseId, fn ($q, $id) => $q->whereHas('session', fn ($s) => $s->where('course_id', $id)))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $data): Homework
    {
        return Homework::create($data);
    }

    public function update(Homework $homework, array $data): Homework
    {
        $homework->fill($data)->save();

        return $homework->fresh();
    }

    public function delete(Homework $homework): void
    {
        $homework->delete();
    }
}
