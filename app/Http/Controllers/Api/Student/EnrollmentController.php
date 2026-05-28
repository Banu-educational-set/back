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
                'Invalid status. Allowed: '.implode(', ', EnrollmentStatus::values()).'.',
                ['status' => ['Invalid value.']],
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
            ->with(['term' => fn ($q) => $q->withCount(['courses', 'enrollments'])])
            ->where('user_id', $request->user()->id)
            ->where('term_id', $term->id)
            ->firstOrFail();

        return ApiResponse::success(new TermEnrollmentResource($enrollment));
    }
}
