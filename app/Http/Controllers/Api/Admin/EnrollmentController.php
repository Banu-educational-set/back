<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Resources\TermEnrollmentResource;
use App\Models\Term;
use App\Models\User;
use App\Services\EnrollmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class EnrollmentController extends Controller
{
    public function __construct(private readonly EnrollmentService $enrollmentService) {}

    public function index(Request $request, Term $term): JsonResponse
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
                $this->enrollmentService->paginateForTerm(
                    $term->id,
                    $status,
                    (int) $request->integer('per_page', 20),
                ),
            ),
        );
    }

    public function store(Request $request, Term $term): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $user = User::findOrFail($data['user_id']);

        if (! $user->hasAnyRole([RoleName::Student->value, RoleName::Missionary->value])) {
            return ApiResponse::error(
                __('errors.user_must_be_student_or_missionary'),
                ['user_id' => [__('errors.invalid_value')]],
                422,
            );
        }

        try {
            $enrollment = $this->enrollmentService->enroll($user, $term);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success(
            new TermEnrollmentResource($enrollment->load('term')),
            'User enrolled.',
            201,
        );
    }
}
