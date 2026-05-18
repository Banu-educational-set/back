<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExamScoringService
{
    public function __construct(private readonly TermCompletionService $completionService) {}

    /**
     * Submit and auto-score an exam attempt.
     *
     * @param  array<int, array{question_id: int, selected_option_id: int|null}>  $answers
     */
    public function submit(User $user, Exam $exam, array $answers): ExamAttempt
    {
        $exam->loadMissing(['questions.options', 'session.course.term']);

        $questionIds = $exam->questions->pluck('id')->all();
        if (count($questionIds) === 0) {
            throw new RuntimeException('Exam has no questions.');
        }

        // Block re-submit if the user has already passed this exam.
        $alreadyPassed = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->where('is_passed', true)
            ->exists();
        if ($alreadyPassed) {
            throw new RuntimeException('You have already passed this exam.');
        }

        $optionByQuestion = [];
        $correctOptionByQuestion = [];
        foreach ($exam->questions as $question) {
            foreach ($question->options as $option) {
                $optionByQuestion[$question->id][$option->id] = $option;
                if ($option->is_correct) {
                    $correctOptionByQuestion[$question->id] = $option->id;
                }
            }
        }

        $cleanAnswers = [];
        foreach ($answers as $answer) {
            $qid = (int) ($answer['question_id'] ?? 0);
            $oid = isset($answer['selected_option_id']) ? (int) $answer['selected_option_id'] : null;

            if (! in_array($qid, $questionIds, true)) {
                throw new RuntimeException("Question {$qid} does not belong to this exam.");
            }
            if ($oid !== null && ! isset($optionByQuestion[$qid][$oid])) {
                throw new RuntimeException("Option {$oid} does not belong to question {$qid}.");
            }
            $cleanAnswers[$qid] = $oid;
        }

        $correctCount = 0;
        foreach ($questionIds as $qid) {
            if (
                isset($cleanAnswers[$qid], $correctOptionByQuestion[$qid])
                && $cleanAnswers[$qid] === $correctOptionByQuestion[$qid]
            ) {
                $correctCount++;
            }
        }

        $score = (int) round(($correctCount / count($questionIds)) * 100);
        $isPassed = $score >= $exam->effectivePassScore();

        $attempt = DB::transaction(function () use ($user, $exam, $cleanAnswers, $correctOptionByQuestion, $score, $isPassed) {
            $attempt = ExamAttempt::create([
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'score' => $score,
                'is_passed' => $isPassed,
                'submitted_at' => now(),
            ]);

            $rows = [];
            foreach ($cleanAnswers as $qid => $oid) {
                $rows[] = [
                    'attempt_id' => $attempt->id,
                    'question_id' => $qid,
                    'selected_option_id' => $oid,
                    'is_correct' => isset($correctOptionByQuestion[$qid]) && $oid === $correctOptionByQuestion[$qid],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($rows) {
                DB::table('exam_answers')->insert($rows);
            }

            return $attempt->load('answers');
        });

        if ($isPassed) {
            $term = $exam->session?->course?->term;
            if ($term) {
                $this->completionService->evaluate($user, $term);
            }
        }

        return $attempt;
    }
}
