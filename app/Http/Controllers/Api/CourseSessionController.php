<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\RoleName;
use App\Http\Requests\Session\StoreSessionRequest;
use App\Http\Requests\Session\UpdateSessionRequest;
use App\Http\Resources\CourseSessionResource;
use App\Models\CourseSession;
use App\Services\CourseSessionService;
use App\Services\PrerequisiteService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseSessionController extends Controller
{
    public function __construct(
        private readonly CourseSessionService $sessionService,
        private readonly PrerequisiteService $prerequisiteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sessions = $this->sessionService->paginate(
            viewer: $request->user(),
            courseId: $request->integer('course_id') ?: null,
            perPage: (int) $request->integer('per_page', 20),
            filters: $request->only(['title', 'session_type', 'from_date', 'to_date']),
        );

        return ApiResponse::success(CourseSessionResource::collection($sessions));
    }

    public function show(Request $request, CourseSession $session): JsonResponse
    {
        $user = $request->user();
        $isStudent = $user && $user->hasRole(RoleName::Student->value)
            && ! $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);

        if ($isStudent) {
            if (! $session->course?->term?->isOpenNow()) {
                return ApiResponse::error(__('errors.term_not_open'), null, 403);
            }

            $unmet = $this->prerequisiteService->sessionUnmetPrerequisites($user, $session);
            if ($unmet !== []) {
                return ApiResponse::error(
                    __('errors.prereq_session_not_met'),
                    ['prerequisite_session_ids' => $unmet],
                    403,
                );
            }
        }

        $session->load(['course.term', 'media'])->loadCount(['exams', 'homeworks']);
        $this->sessionService->attachPrerequisiteSessions(collect([$session]));

        return ApiResponse::success(new CourseSessionResource($session));
    }

    public function store(StoreSessionRequest $request): JsonResponse
    {
        $session = $this->sessionService->create($request->validated(), $request->user());

        return ApiResponse::success(new CourseSessionResource($session), __('messages.session_created'), 201);
    }

    public function update(UpdateSessionRequest $request, CourseSession $session): JsonResponse
    {
        $updated = $this->sessionService->update($session, $request->validated(), $request->user());

        return ApiResponse::success(new CourseSessionResource($updated), __('messages.session_updated'));
    }

    public function destroy(CourseSession $session): JsonResponse
    {
        $this->authorize('delete', $session);
        $this->sessionService->delete($session);

        return ApiResponse::success(null, __('messages.session_deleted'));
    }
}
