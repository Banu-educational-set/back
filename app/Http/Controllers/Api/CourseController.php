<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(private readonly CourseService $courseService) {}

    public function index(Request $request): JsonResponse
    {
        $courses = $this->courseService->paginate(
            viewer: $request->user(),
            termId: $request->integer('term_id') ?: null,
            perPage: (int) $request->integer('per_page', 20),
        );

        return ApiResponse::success(CourseResource::collection($courses));
    }

    public function show(Course $course): JsonResponse
    {
        return ApiResponse::success(new CourseResource($course->load(['term', 'teacher'])));
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = $this->courseService->create($request->validated(), $request->user());

        return ApiResponse::success(new CourseResource($course), 'Course created.', 201);
    }

    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $updated = $this->courseService->update($course, $request->validated(), $request->user());

        return ApiResponse::success(new CourseResource($updated), 'Course updated.');
    }

    public function destroy(Course $course): JsonResponse
    {
        $this->authorize('delete', $course);
        $this->courseService->delete($course);

        return ApiResponse::success(null, 'Course deleted.');
    }
}
