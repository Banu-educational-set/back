<?php

namespace App\Http\Controllers\Api;

use App\Enums\HomeworkSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Homework\ReviewSubmissionRequest;
use App\Http\Requests\Homework\StoreHomeworkRequest;
use App\Http\Requests\Homework\SubmitHomeworkRequest;
use App\Http\Requests\Homework\UpdateHomeworkRequest;
use App\Http\Resources\HomeworkResource;
use App\Http\Resources\HomeworkSubmissionResource;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Services\HomeworkReviewService;
use App\Services\HomeworkService;
use App\Services\HomeworkSubmissionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class HomeworkController extends Controller
{
    public function __construct(
        private readonly HomeworkSubmissionService $submissionService,
        private readonly HomeworkReviewService $reviewService,
        private readonly HomeworkService $homeworkService,
    ) {}

    public function store(StoreHomeworkRequest $request): JsonResponse
    {
        $homework = $this->homeworkService->create($request->validated());

        return ApiResponse::success(new HomeworkResource($homework), 'Homework created.', 201);
    }

    public function update(UpdateHomeworkRequest $request, Homework $homework): JsonResponse
    {
        $updated = $this->homeworkService->update($homework, $request->validated());

        return ApiResponse::success(new HomeworkResource($updated), 'Homework updated.');
    }

    public function destroy(Homework $homework): JsonResponse
    {
        $this->authorize('delete', $homework);
        $this->homeworkService->delete($homework);

        return ApiResponse::success(null, 'Homework deleted.');
    }

    public function submit(SubmitHomeworkRequest $request, Homework $homework): JsonResponse
    {
        try {
            $submission = $this->submissionService->submit(
                $request->user(),
                $homework,
                (int) $request->integer('media_id'),
            );
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success(
            new HomeworkSubmissionResource($submission->load('media')),
            'Homework submitted.',
            201,
        );
    }

    public function review(ReviewSubmissionRequest $request, HomeworkSubmission $submission): JsonResponse
    {
        $status = HomeworkSubmissionStatus::from($request->validated('status'));
        $feedback = $request->validated('teacher_feedback');

        $reviewed = match ($status) {
            HomeworkSubmissionStatus::Accepted => $this->reviewService->accept($submission, $request->user(), $feedback),
            HomeworkSubmissionStatus::Denied => $this->reviewService->deny($submission, $request->user(), $feedback),
            default => $submission,
        };

        return ApiResponse::success(
            new HomeworkSubmissionResource($reviewed->load('media')),
            'Submission reviewed.',
        );
    }
}
