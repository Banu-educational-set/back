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
     * Begin (or resume) an attempt for the user on this exam. Idempotent
     * while an un-submitted attempt exists. Errors if the user has already
     * passed, or the term is not currently open.
     */
    public function start(User $user, Exam $exam): ExamAttempt
    {
        $exam->loadMissing(['session.course.term', 'questions']);

        $term = $exam->session?->course?->term;
        if ($term && ! $term->isOpenNow()) {
            throw new RuntimeException('This term is not currently open.');
        }

        $questionSum = (int) $exam->questions->sum('score');
        if ($questionSum !== (int) $exam->score) {
            throw new RuntimeException(sprintf(
                'Exam is misconfigured: sum of question scores (%d) does not equal exam score (%d).',
                $questionSum,
                (int) $exam->score,
            ));
        }

        $alreadyPassed = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->where('is_passed', true)
            ->exists();
        if ($alreadyPassed) {
            throw new RuntimeException('You have already passed this exam.');
        }

        $existing = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->whereNull('submitted_at')
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return ExamAttempt::create([
            'user_id' => $user->id,
            'exam_id' => $exam->id,
            'started_at' => now(),
            'score' => 0,
            'is_passed' => false,
        ]);
    }

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

        $activeAttempt = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->whereNull('submitted_at')
            ->latest('id')
            ->first();
        if (! $activeAttempt) {
            throw new RuntimeException('You must start the exam before submitting.');
        }

        $deadline = $activeAttempt->deadline_at;
        if ($deadline && now()->gt($deadline)) {
            throw new RuntimeException('The exam window has closed.');
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

        $scoreByQuestion = [];
        foreach ($exam->questions as $question) {
            $scoreByQuestion[$question->id] = (int) $question->score;
        }

        $score = 0;
        foreach ($questionIds as $qid) {
            if (
                isset($cleanAnswers[$qid], $correctOptionByQuestion[$qid])
                && $cleanAnswers[$qid] === $correctOptionByQuestion[$qid]
            ) {
                $score += $scoreByQuestion[$qid] ?? 0;
            }
        }

        $isPassed = $score >= (int) $exam->minimum_score;

        $attempt = DB::transaction(function () use ($activeAttempt, $cleanAnswers, $correctOptionByQuestion, $score, $isPassed) {
            $activeAttempt->update([
                'score' => $score,
                'is_passed' => $isPassed,
                'submitted_at' => now(),
            ]);

            $rows = [];
            foreach ($cleanAnswers as $qid => $oid) {
                $rows[] = [
                    'attempt_id' => $activeAttempt->id,
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

            return $activeAttempt->fresh('answers');
        });

        // Re-evaluate term completion on every submission — failing this exam
        // can still raise the student's term grade thanks to the new
        // point-sum model.
        $term = $exam->session?->course?->term;
        if ($term) {
            $this->completionService->evaluate($user, $term);
        }

        return $attempt;
    }
}
