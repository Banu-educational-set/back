<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExamAttemptResource;
use App\Http\Resources\HomeworkResource;
use App\Models\ExamAttempt;
use App\Models\Homework;
use App\Models\TermEnrollment;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultsController extends Controller
{
    public function examResults(Request $request): JsonResponse
    {
        $attempts = ExamAttempt::query()
            ->where('user_id', $request->user()->id)
            ->with('exam:id,title,session_id')
            ->orderByDesc('id')
            ->paginate(20);

        return ApiResponse::success(ExamAttemptResource::collection($attempts));
    }

    public function homeworks(Request $request): JsonResponse
    {
        $termIds = TermEnrollment::query()
            ->where('user_id', $request->user()->id)
            ->pluck('term_id');

        $homeworks = Homework::query()
            ->with(['session.course.term', 'media'])
            ->withCount(['submissions as submitters_count'])
            ->where('is_active', true)
            ->whereHas('session.course', function ($q) use ($termIds) {
                $q->whereIn('term_id', $termIds)
                    ->where('is_active', true);
            })
            ->orderByDesc('id')
            ->paginate(20);

        return ApiResponse::success(HomeworkResource::collection($homeworks));
    }
}
