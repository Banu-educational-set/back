<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Term;
use App\Services\AttendanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    public function term(Request $request, Term $term): JsonResponse
    {
        $users = $this->attendance
            ->termAttendees($term->id)
            ->paginate((int) $request->integer('per_page', 20));

        return ApiResponse::success(UserResource::collection($users));
    }

    public function course(Request $request, Course $course): JsonResponse
    {
        $users = $this->attendance
            ->courseAttendees($course->id)
            ->paginate((int) $request->integer('per_page', 20));

        return ApiResponse::success(UserResource::collection($users));
    }

    public function session(Request $request, CourseSession $session): JsonResponse
    {
        $users = $this->attendance
            ->sessionAttendees($session->id)
            ->paginate((int) $request->integer('per_page', 20));

        return ApiResponse::success(UserResource::collection($users));
    }
}
