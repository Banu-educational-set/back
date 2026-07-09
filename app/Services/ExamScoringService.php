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
            throw new RuntimeException(__('errors.term_not_open'));
        }

        $questionSum = (int) $exam->questions->sum('score');
        if ($questionSum !== (int) $exam->score) {
            throw new RuntimeException(__('errors.exam_misconfigured'));
        }

        $alreadyPassed = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->where('is_passed', true)
            ->exists();
        if ($alreadyPassed) {
            throw new RuntimeException(__('errors.exam_already_passed'));
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
            throw new RuntimeException(__('errors.exam_no_questions'));
        }

        // Block re-submit if the user has already passed this exam.
        $alreadyPassed = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->where('is_passed', true)
            ->exists();
        if ($alreadyPassed) {
            throw new RuntimeException(__('errors.exam_already_passed'));
        }

        $activeAttempt = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->whereNull('submitted_at')
            ->latest('id')
            ->first();
        if (! $activeAttempt) {
            throw new RuntimeException(__('errors.exam_not_started'));
        }

        $deadline = $activeAttempt->deadline_at;
        if ($deadline && now()->gt($deadline)) {
            throw new RuntimeException(__('errors.exam_window_closed'));
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
                throw new RuntimeException(__('errors.answer_invalid_question'));
            }
            if ($oid !== null && ! isset($optionByQuestion[$qid][$oid])) {
                throw new RuntimeException(__('errors.answer_invalid_option'));
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

        // Failure path: if this attempt was a fail AND the user has now used
        // up all retries on this exam, wipe every exam_attempt and
        // homework_submission for the user inside this session so they can
        // start the session from scratch.
        $resetSession = false;
        if (! $isPassed) {
            $maxAttempts = (int) config('education.max_exam_attempts', 3);
            $failedCount = ExamAttempt::query()
                ->where('user_id', $user->id)
                ->where('exam_id', $exam->id)
                ->whereNotNull('submitted_at')
                ->where('is_passed', false)
                ->count();

            if ($maxAttempts > 0 && $failedCount >= $maxAttempts) {
                $this->resetSessionDataForUser($user, $exam);
                $resetSession = true;
            }
        }

        // Re-evaluate term completion on every submission — failing this exam
        // can still raise the student's term grade thanks to the new
        // point-sum model. Skip when we just wiped the session (no change to
        // evaluate anyway, and the freshly-deleted attempt is gone).
        if (! $resetSession) {
            $term = $exam->session?->course?->term;
            if ($term) {
                $this->completionService->evaluate($user, $term);
            }
        }

        return $attempt;
    }

    /**
     * Delete every exam_attempt and homework_submission for the user under
     * the same session as $exam. Called when the user exhausts the retake
     * limit on any exam in that session.
     */
    private function resetSessionDataForUser(User $user, Exam $exam): void
    {
        $sessionId = $exam->session_id;
        if (! $sessionId) {
            return;
        }

        DB::transaction(function () use ($user, $sessionId) {
            $examIds = DB::table('exams')
                ->where('session_id', $sessionId)
                ->pluck('id');

            if ($examIds->isNotEmpty()) {
                $attemptIds = DB::table('exam_attempts')
                    ->where('user_id', $user->id)
                    ->whereIn('exam_id', $examIds)
                    ->pluck('id');

                if ($attemptIds->isNotEmpty()) {
                    DB::table('exam_answers')->whereIn('attempt_id', $attemptIds)->delete();
                    DB::table('exam_attempts')->whereIn('id', $attemptIds)->delete();
                }
            }

            $homeworkIds = DB::table('homeworks')
                ->where('session_id', $sessionId)
                ->pluck('id');

            if ($homeworkIds->isNotEmpty()) {
                DB::table('homework_submissions')
                    ->where('user_id', $user->id)
                    ->whereIn('homework_id', $homeworkIds)
                    ->delete();
            }
        });
    }
}
