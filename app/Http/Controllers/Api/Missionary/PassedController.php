<?php

namespace App\Http\Controllers\Api\Missionary;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\CourseSessionResource;
use App\Http\Resources\TermEnrollmentResource;
use App\Services\MissionaryPassedService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PassedController extends Controller
{
    public function __construct(private readonly MissionaryPassedService $service) {}

    public function terms(Request $request): JsonResponse
    {
        return ApiResponse::success(
            TermEnrollmentResource::collection(
                $this->service->terms($request->user(), (int) $request->integer('per_page', 20)),
            ),
        );
    }

    public function courses(Request $request): JsonResponse
    {
        return ApiResponse::success(
            CourseResource::collection(
                $this->service->courses(
                    $request->user(),
                    $request->filled('term_id') ? (int) $request->input('term_id') : null,
                    (int) $request->integer('per_page', 20),
                ),
            ),
        );
    }

    public function sessions(Request $request): JsonResponse
    {
        return ApiResponse::success(
            CourseSessionResource::collection(
                $this->service->sessions(
                    $request->user(),
                    $request->filled('course_id') ? (int) $request->input('course_id') : null,
                    (int) $request->integer('per_page', 20),
                ),
            ),
        );
    }
}
