<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExamAttemptResource;
use App\Http\Resources\ExamResource;
use App\Http\Resources\HomeworkResource;
use App\Models\Exam;
use App\Models\Homework;
use App\Models\TermEnrollment;
use Illuminate\Support\Facades\DB;
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

        $sessionId = $request->filled('session_id') ? (int) $request->input('session_id') : null;
        $courseId = $request->filled('course_id') ? (int) $request->input('course_id') : null;
        $termId = $request->filled('term_id') ? (int) $request->input('term_id') : null;

        $homeworks = Homework::query()
            ->with(['session.course.term', 'media'])
            ->withCount(['submissions as submitters_count'])
            ->where('is_active', true)
            ->whereHas('session.course', function ($q) use ($termIds, $termId, $courseId) {
                $q->whereIn('term_id', $termIds)
                    ->where('is_active', true)
                    ->when($termId, fn ($q, $id) => $q->where('term_id', $id))
                    ->when($courseId, fn ($q, $id) => $q->where('id', $id));
            })
            ->when($sessionId, fn ($q, $id) => $q->where('session_id', $id))
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 20));

        return ApiResponse::success(HomeworkResource::collection($homeworks));
    }

    public function exams(Request $request): JsonResponse
    {
        $termIds = TermEnrollment::query()
            ->where('user_id', $request->user()->id)
            ->pluck('term_id');

        $sessionId = $request->filled('session_id') ? (int) $request->input('session_id') : null;
        $courseId = $request->filled('course_id') ? (int) $request->input('course_id') : null;
        $termId = $request->filled('term_id') ? (int) $request->input('term_id') : null;

        $exams = Exam::query()
            ->with('session.course.term')
            ->withCount('questions')
            ->withCount(['attempts as submitters_count' => fn ($q) => $q->select(DB::raw('count(distinct user_id)'))])
            ->where('is_active', true)
            ->whereHas('session.course', function ($q) use ($termIds, $termId, $courseId) {
                $q->whereIn('term_id', $termIds)
                    ->where('is_active', true)
                    ->when($termId, fn ($q, $id) => $q->where('term_id', $id))
                    ->when($courseId, fn ($q, $id) => $q->where('id', $id));
            })
            ->when($sessionId, fn ($q, $id) => $q->where('session_id', $id))
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 20));

        return ApiResponse::success(ExamResource::collection($exams));
    }
}
