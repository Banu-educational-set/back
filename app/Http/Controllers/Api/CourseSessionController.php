<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Session\StoreSessionRequest;
use App\Http\Requests\Session\UpdateSessionRequest;
use App\Http\Resources\CourseSessionResource;
use App\Models\CourseSession;
use App\Services\CourseSessionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseSessionController extends Controller
{
    public function __construct(private readonly CourseSessionService $sessionService) {}

    public function index(Request $request): JsonResponse
    {
        $sessions = $this->sessionService->paginate(
            viewer: $request->user(),
            courseId: $request->integer('course_id') ?: null,
            perPage: (int) $request->integer('per_page', 20),
        );

        return ApiResponse::success(CourseSessionResource::collection($sessions));
    }

    public function show(CourseSession $session): JsonResponse
    {
        return ApiResponse::success(new CourseSessionResource($session->load(['course', 'media'])));
    }

    public function store(StoreSessionRequest $request): JsonResponse
    {
        $session = $this->sessionService->create($request->validated(), $request->user());

        return ApiResponse::success(new CourseSessionResource($session), 'Session created.', 201);
    }

    public function update(UpdateSessionRequest $request, CourseSession $session): JsonResponse
    {
        $updated = $this->sessionService->update($session, $request->validated(), $request->user());

        return ApiResponse::success(new CourseSessionResource($updated), 'Session updated.');
    }

    public function destroy(CourseSession $session): JsonResponse
    {
        $this->authorize('delete', $session);
        $this->sessionService->delete($session);

        return ApiResponse::success(null, 'Session deleted.');
    }
}
