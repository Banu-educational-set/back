<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleName;
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
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Services\ExamManagementService;
use App\Services\ExamScoringService;
use App\Services\PrerequisiteService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamScoringService $scoringService,
        private readonly ExamManagementService $managementService,
        private readonly PrerequisiteService $prerequisiteService,
    ) {}

    public function forSession(Request $request, CourseSession $session): JsonResponse
    {
        $user = $request->user();
        $isStudent = $user && $user->hasRole(RoleName::Student->value)
            && ! $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);

        if ($isStudent) {
            $unmet = $this->prerequisiteService->sessionUnmetPrerequisites($user, $session);
            if ($unmet !== []) {
                return ApiResponse::error(
                    'Prerequisites not met for this session.',
                    ['prerequisite_session_ids' => $unmet],
                    403,
                );
            }
        }

        $exams = Exam::query()
            ->with('session.course.term')
            ->withCount('questions')
            ->withCount(['attempts as submitters_count' => fn ($q) => $q->select(\Illuminate\Support\Facades\DB::raw('count(distinct user_id)'))])
            ->where('session_id', $session->id)
            ->when($isStudent, fn ($q) => $q->where('is_active', true))
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 20));

        return ApiResponse::success(ExamResource::collection($exams));
    }

    public function index(Request $request): JsonResponse
    {
        $exams = $this->managementService->paginate(
            sessionId: $request->filled('session_id') ? (int) $request->input('session_id') : null,
            courseId: $request->filled('course_id') ? (int) $request->input('course_id') : null,
            termId: $request->filled('term_id') ? (int) $request->input('term_id') : null,
            perPage: (int) $request->integer('per_page', 20),
        );

        return ApiResponse::success(ExamResource::collection($exams));
    }

    public function show(Request $request, Exam $exam): JsonResponse
    {
        $this->authorize('view', $exam);

        $exam->load(['questions.options', 'session.course.term'])->loadCount([
            'questions',
            'attempts as submitters_count' => fn ($q) => $q->select(\Illuminate\Support\Facades\DB::raw('count(distinct user_id)')),
        ]);

        $user = $request->user();
        $isStudent = $user && $user->hasRole(RoleName::Student->value)
            && ! $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);
        if ($isStudent && $exam->is_random) {
            $exam->setRelation('questions', $exam->questions->shuffle()->values());
        }

        return ApiResponse::success(new ExamResource($exam));
    }

    public function attempts(Request $request, Exam $exam): JsonResponse
    {
        $attempts = $exam->attempts()
            ->with(['user.roles', 'user.avatar'])
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 20));

        return ApiResponse::success(ExamAttemptResource::collection($attempts));
    }

    public function start(Request $request, Exam $exam): JsonResponse
    {
        $this->authorize('submit', $exam);

        try {
            $attempt = $this->scoringService->start($request->user(), $exam);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success(new ExamAttemptResource($attempt), 'Exam started.', 201);
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
