<?php

namespace App\Http\Controllers\Api\Student;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TermEnrollmentResource;
use App\Models\Term;
use App\Models\TermEnrollment;
use App\Services\EnrollmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(private readonly EnrollmentService $enrollmentService) {}

    public function myTerms(Request $request): JsonResponse
    {
        $status = $request->input('status');
        if ($status !== null && ! in_array($status, EnrollmentStatus::values(), true)) {
            return ApiResponse::error(
                __('errors.invalid_value_allowed', ['label' => __('validation.attributes.status'), 'allowed' => implode('، ', EnrollmentStatus::values())]),
                ['status' => [__('errors.invalid_value')]],
                422,
            );
        }

        return ApiResponse::success(
            TermEnrollmentResource::collection(
                $this->enrollmentService->paginateForUser(
                    $request->user(),
                    $status,
                    (int) $request->integer('per_page', 20),
                ),
            ),
        );
    }

    public function showTerm(Request $request, Term $term): JsonResponse
    {
        $enrollment = TermEnrollment::query()
            ->with(['term' => fn ($q) => $q
                ->withCount(['courses', 'enrollments', 'termSessions as sessions_count'])
                ->withCount([
                    'termSessions as exams_count' => fn ($qq) => $qq->join('exams', 'exams.session_id', '=', 'course_sessions.id')->select(\Illuminate\Support\Facades\DB::raw('count(distinct exams.id)')),
                    'termSessions as homeworks_count' => fn ($qq) => $qq->join('homeworks', 'homeworks.session_id', '=', 'course_sessions.id')->select(\Illuminate\Support\Facades\DB::raw('count(distinct homeworks.id)')),
                ])
            ])
            ->where('user_id', $request->user()->id)
            ->where('term_id', $term->id)
            ->firstOrFail();

        return ApiResponse::success(new TermEnrollmentResource($enrollment));
    }
}
