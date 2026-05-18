<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\TermEnrollmentResource;
use App\Models\Term;
use App\Models\TermEnrollment;
use App\Services\EnrollmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class EnrollmentController extends Controller
{
    public function __construct(private readonly EnrollmentService $enrollmentService) {}

    public function enroll(Request $request, Term $term): JsonResponse
    {
        try {
            $enrollment = $this->enrollmentService->enroll($request->user(), $term);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success(
            new TermEnrollmentResource($enrollment->load('term')),
            'Enrolled.',
            201,
        );
    }

    public function myTerms(Request $request): JsonResponse
    {
        return ApiResponse::success(
            TermEnrollmentResource::collection(
                $this->enrollmentService->paginateForUser(
                    $request->user(),
                    (int) $request->integer('per_page', 20),
                ),
            ),
        );
    }

    public function showTerm(Request $request, Term $term): JsonResponse
    {
        $enrollment = TermEnrollment::query()
            ->with('term')
            ->where('user_id', $request->user()->id)
            ->where('term_id', $term->id)
            ->firstOrFail();

        return ApiResponse::success(new TermEnrollmentResource($enrollment));
    }
}
