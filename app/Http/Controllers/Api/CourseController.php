<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\RoleName;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseService;
use App\Services\PrerequisiteService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courseService,
        private readonly PrerequisiteService $prerequisiteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $courses = $this->courseService->paginate(
            viewer: $request->user(),
            termId: $request->filled('term_id') ? (int) $request->input('term_id') : null,
            perPage: (int) $request->integer('per_page', 20),
            filters: $request->only(['title', 'status', 'from_date', 'to_date']),
        );

        return ApiResponse::success(CourseResource::collection($courses));
    }

    public function show(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        $isStudent = $user && $user->hasRole(RoleName::Student->value)
            && ! $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);

        if ($isStudent) {
            if (! $course->term?->isOpenNow()) {
                return ApiResponse::error(__('errors.term_not_open'), null, 403);
            }

            $unmet = $this->prerequisiteService->courseUnmetPrerequisites($user, $course);
            if ($unmet !== []) {
                return ApiResponse::error(
                    __('errors.prereq_course_not_met'),
                    ['prerequisite_course_ids' => $unmet],
                    403,
                );
            }
        }

        $course->load(['term', 'teacher'])->loadCount([
            'sessions',
            'sessionExams as exams_count',
            'sessionHomeworks as homeworks_count',
        ]);
        $this->courseService->attachPrerequisiteCourses(collect([$course]));

        return ApiResponse::success(new CourseResource($course));
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = $this->courseService->create($request->validated(), $request->user());

        return ApiResponse::success(new CourseResource($course), __('messages.course_created'), 201);
    }

    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $updated = $this->courseService->update($course, $request->validated(), $request->user());

        return ApiResponse::success(new CourseResource($updated), __('messages.course_updated'));
    }

    public function destroy(Course $course): JsonResponse
    {
        $this->authorize('delete', $course);
        $this->courseService->delete($course);

        return ApiResponse::success(null, __('messages.course_deleted'));
    }
}
