<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full per-attempt review payload: scoring + every question, every option,
 * which one(s) the student picked, and which is correct. Used to render
 * the student-facing "exam result" review screen.
 */
class ExamAttemptDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $selectedByQuestion = $this->answers
            ->keyBy('question_id')
            ->map(fn ($a) => $a->selected_option_id);

        $questions = $this->exam->questions->map(function ($question) use ($selectedByQuestion) {
            $selected = $selectedByQuestion->get($question->id);

            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'position' => (int) $question->position,
                'score' => (int) $question->score,
                'selected_option_id' => $selected,
                'options' => $question->options->map(fn ($o) => [
                    'id' => $o->id,
                    'option_text' => $o->option_text,
                    'is_correct' => (bool) $o->is_correct,
                    'is_selected' => $selected === $o->id,
                ])->all(),
            ];
        });

        $correctCount = (int) $this->answers->where('is_correct', true)->count();
        $totalQuestions = $this->exam->questions->count();

        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'score' => (int) $this->score,
            'is_passed' => (bool) $this->is_passed,
            'correct_count' => $correctCount,
            'total_questions' => $totalQuestions,
            'started_at' => $this->started_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'exam' => [
                'id' => $this->exam->id,
                'title' => $this->exam->title,
                'description' => $this->exam->description,
                'score' => (int) $this->exam->score,
                'minimum_score' => (int) $this->exam->minimum_score,
                'duration_minutes' => $this->exam->duration_minutes,
                'session' => $this->exam->session ? [
                    'id' => $this->exam->session->id,
                    'title' => $this->exam->session->title,
                    'course' => $this->exam->session->course ? [
                        'id' => $this->exam->session->course->id,
                        'title' => $this->exam->session->course->title,
                        'term' => $this->exam->session->course->term ? [
                            'id' => $this->exam->session->course->term->id,
                            'title' => $this->exam->session->course->term->title,
                            'score' => (int) $this->exam->session->course->term->score,
                            'minimum_score' => (int) $this->exam->session->course->term->minimum_score,
                        ] : null,
                    ] : null,
                ] : null,
            ],
            'questions' => $questions->all(),
        ];
    }
}
