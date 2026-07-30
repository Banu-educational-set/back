<?php

namespace App\Http\Controllers\Api;

use App\Enums\HomeworkSubmissionStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Homework\ReviewSubmissionRequest;
use App\Http\Requests\Homework\StoreHomeworkRequest;
use App\Http\Requests\Homework\SubmitHomeworkRequest;
use App\Http\Requests\Homework\UpdateHomeworkRequest;
use App\Http\Resources\HomeworkResource;
use App\Http\Resources\HomeworkSubmissionResource;
use App\Models\CourseSession;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Services\HomeworkReviewService;
use App\Services\HomeworkService;
use App\Services\HomeworkSubmissionService;
use App\Services\PrerequisiteService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    public function __construct(
        private readonly HomeworkSubmissionService $submissionService,
        private readonly HomeworkReviewService $reviewService,
        private readonly HomeworkService $homeworkService,
        private readonly PrerequisiteService $prerequisiteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $homeworks = $this->homeworkService->paginate(
            sessionId: $request->filled('session_id') ? (int) $request->input('session_id') : null,
            courseId: $request->filled('course_id') ? (int) $request->input('course_id') : null,
            termId: $request->filled('term_id') ? (int) $request->input('term_id') : null,
            perPage: (int) $request->integer('per_page', 20),
            filters: $request->only(['title', 'from_date', 'to_date']),
        );

        return ApiResponse::success(HomeworkResource::collection($homeworks));
    }

    public function show(Homework $homework): JsonResponse
    {
        $homework->load(['session.course.term', 'media'])->loadCount(['submissions as submitters_count']);

        return ApiResponse::success(new HomeworkResource($homework));
    }

    public function submissions(Request $request, Homework $homework): JsonResponse
    {
        $submissions = $homework->submissions()
            ->with(['user.roles', 'user.avatar', 'media'])
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 20));

        return ApiResponse::success(HomeworkSubmissionResource::collection($submissions));
    }

    public function forSession(Request $request, CourseSession $session): JsonResponse
    {
        $user = $request->user();
        $isStudent = $user && $user->hasRole(RoleName::Student->value)
            && ! $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);

        if ($isStudent) {
            $unmet = $this->prerequisiteService->sessionUnmetPrerequisites($user, $session);
            if ($unmet !== []) {
                return ApiResponse::error(
                    __('errors.prereq_session_not_met'),
                    ['prerequisite_session_ids' => $unmet],
                    403,
                );
            }
        }

        $homeworks = Homework::query()
            ->with(['session.course.term', 'media'])
            ->withCount(['submissions as submitters_count'])
            ->where('session_id', $session->id)
            ->when($isStudent, fn ($q) => $q->where('is_active', true))
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 20));

        return ApiResponse::success(HomeworkResource::collection($homeworks));
    }

    public function store(StoreHomeworkRequest $request): JsonResponse
    {
        $homework = $this->homeworkService->create($request->validated(), $request->user());

        return ApiResponse::success(new HomeworkResource($homework), __('messages.homework_created'), 201);
    }

    public function update(UpdateHomeworkRequest $request, Homework $homework): JsonResponse
    {
        $updated = $this->homeworkService->update($homework, $request->validated(), $request->user());

        return ApiResponse::success(new HomeworkResource($updated), __('messages.homework_updated'));
    }

    public function destroy(Homework $homework): JsonResponse
    {
        $this->authorize('delete', $homework);
        $this->homeworkService->delete($homework);

        return ApiResponse::success(null, __('messages.homework_deleted'));
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
