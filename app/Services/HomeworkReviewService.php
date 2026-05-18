<?php

namespace App\Services;

use App\Enums\HomeworkSubmissionStatus;
use App\Models\HomeworkSubmission;
use App\Models\User;

class HomeworkReviewService
{
    public function __construct(private readonly TermCompletionService $completionService) {}

    public function accept(HomeworkSubmission $submission, User $reviewer, ?string $feedback = null): HomeworkSubmission
    {
        return $this->review($submission, $reviewer, HomeworkSubmissionStatus::Accepted, $feedback);
    }

    public function deny(HomeworkSubmission $submission, User $reviewer, ?string $feedback = null): HomeworkSubmission
    {
        return $this->review($submission, $reviewer, HomeworkSubmissionStatus::Denied, $feedback);
    }

    private function review(
        HomeworkSubmission $submission,
        User $reviewer,
        HomeworkSubmissionStatus $status,
        ?string $feedback,
    ): HomeworkSubmission {
        $submission->update([
            'status' => $status->value,
            'teacher_feedback' => $feedback,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        if ($status === HomeworkSubmissionStatus::Accepted) {
            $term = $submission->loadMissing('homework.session.course.term')
                ->homework?->session?->course?->term;

            if ($term && $submission->user) {
                $this->completionService->evaluate($submission->user, $term);
            }
        }

        return $submission->fresh();
    }
}
