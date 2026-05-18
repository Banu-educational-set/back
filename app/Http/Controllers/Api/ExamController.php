<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\StoreExamRequest;
use App\Http\Requests\Exam\StoreOptionRequest;
use App\Http\Requests\Exam\StoreQuestionRequest;
use App\Http\Requests\Exam\SubmitExamRequest;
use App\Http\Requests\Exam\UpdateExamRequest;
use App\Http\Resources\ExamAttemptResource;
use App\Http\Resources\ExamOptionResource;
use App\Http\Resources\ExamQuestionResource;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Services\ExamManagementService;
use App\Services\ExamScoringService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamScoringService $scoringService,
        private readonly ExamManagementService $managementService,
    ) {}

    public function show(Request $request, Exam $exam): JsonResponse
    {
        $this->authorize('view', $exam);

        return ApiResponse::success(new ExamResource($exam->load('questions.options')));
    }

    public function submit(SubmitExamRequest $request, Exam $exam): JsonResponse
    {
        $this->authorize('submit', $exam);

        try {
            $attempt = $this->scoringService->submit(
                $request->user(),
                $exam,
                $request->validated('answers'),
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success(new ExamAttemptResource($attempt), 'Exam submitted.', 201);
    }

    public function store(StoreExamRequest $request): JsonResponse
    {
        $exam = $this->managementService->createExam($request->validated());

        return ApiResponse::success(new ExamResource($exam), 'Exam created.', 201);
    }

    public function update(UpdateExamRequest $request, Exam $exam): JsonResponse
    {
        $updated = $this->managementService->updateExam($exam, $request->validated());

        return ApiResponse::success(new ExamResource($updated), 'Exam updated.');
    }

    public function destroy(Exam $exam): JsonResponse
    {
        $this->authorize('delete', $exam);
        $this->managementService->deleteExam($exam);

        return ApiResponse::success(null, 'Exam deleted.');
    }

    public function addQuestion(StoreQuestionRequest $request, Exam $exam): JsonResponse
    {
        try {
            $question = $this->managementService->addQuestion($exam, $request->validated());
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success(new ExamQuestionResource($question), 'Question added.', 201);
    }

    public function addOption(StoreOptionRequest $request, ExamQuestion $question): JsonResponse
    {
        $option = $this->managementService->addOption($question, $request->validated());

        return ApiResponse::success(new ExamOptionResource($option), 'Option added.', 201);
    }
}
