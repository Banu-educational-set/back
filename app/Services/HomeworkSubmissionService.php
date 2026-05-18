<?php

namespace App\Services;

use App\Enums\HomeworkSubmissionStatus;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HomeworkSubmissionService
{
    public function __construct(private readonly MediaService $mediaService) {}

    /**
     * Create or replace the student's submission for a homework, attaching the
     * already-uploaded media row identified by $mediaId. Re-submitting an
     * already-accepted homework is rejected so an accepted term cannot
     * accidentally regress.
     */
    public function submit(User $user, Homework $homework, int $mediaId): HomeworkSubmission
    {
        return DB::transaction(function () use ($user, $homework, $mediaId) {
            $existing = HomeworkSubmission::query()
                ->where('homework_id', $homework->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing && $existing->status === HomeworkSubmissionStatus::Accepted) {
                throw new \RuntimeException('This homework has already been accepted.');
            }

            $submission = HomeworkSubmission::updateOrCreate(
                ['homework_id' => $homework->id, 'user_id' => $user->id],
                [
                    'status' => HomeworkSubmissionStatus::Pending->value,
                    'teacher_feedback' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ],
            );

            $media = Media::findOrFail($mediaId);
            $this->mediaService->attachTo(
                media: $media,
                owner: $submission,
                uploader: $user,
                collection: 'submission_files',
                singleFile: true,
            );

            return $submission->fresh(['media']);
        });
    }
}
