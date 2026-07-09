<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamOption;
use App\Models\ExamQuestion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExamManagementService
{
    public function paginate(?int $sessionId, ?int $courseId, ?int $termId, int $perPage = 20): LengthAwarePaginator
    {
        return Exam::query()
            ->with('session.course.term')
            ->withCount('questions')
            ->withCount(['attempts as submitters_count' => fn ($q) => $q->select(DB::raw('count(distinct user_id)'))])
            ->when($sessionId, fn ($q, $id) => $q->where('session_id', $id))
            ->when($courseId, fn ($q, $id) => $q->whereHas('session', fn ($s) => $s->where('course_id', $id)))
            ->when($termId, fn ($q, $id) => $q->whereHas('session.course', fn ($c) => $c->where('term_id', $id)))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function createExam(array $data): Exam
    {
        return Exam::create($data);
    }

    public function updateExam(Exam $exam, array $data): Exam
    {
        $exam->fill($data)->save();

        return $exam->fresh();
    }

    public function deleteExam(Exam $exam): void
    {
        $exam->delete();
    }

    /**
     * Create a question and its options atomically.
     * Exactly one option must be marked correct.
     */
    public function addQuestion(Exam $exam, array $data): ExamQuestion
    {
        $options = $data['options'] ?? [];
        $correct = array_filter($options, fn ($o) => ! empty($o['is_correct']));
        if (count($correct) !== 1) {
            throw new RuntimeException(__('errors.each_question_one_correct'));
        }

        return DB::transaction(function () use ($exam, $data, $options) {
            $position = (int) ($data['position'] ?? ($exam->questions()->max('position') + 1));

            $question = $exam->questions()->create([
                'question_text' => $data['question_text'],
                'position' => $position,
                'score' => (int) $data['score'],
            ]);

            foreach ($options as $option) {
                $question->options()->create([
                    'option_text' => $option['option_text'],
                    'is_correct' => (bool) ($option['is_correct'] ?? false),
                ]);
            }

            return $question->load('options');
        });
    }

    public function addOption(ExamQuestion $question, array $data): ExamOption
    {
        return DB::transaction(function () use ($question, $data) {
            if (! empty($data['is_correct'])) {
                $question->options()->where('is_correct', true)->update(['is_correct' => false]);
            }

            return $question->options()->create([
                'option_text' => $data['option_text'],
                'is_correct' => (bool) ($data['is_correct'] ?? false),
            ]);
        });
    }
}
