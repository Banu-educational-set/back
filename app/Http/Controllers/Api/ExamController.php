<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\StoreExamRequest;
use App\Http\Requests\Exam\StoreOptionRequest;
use App\Http\Requests\Exam\StoreQuestionRequest;
use App\Http\Requests\Exam\SubmitExamRequest;
use App\Http\Requests\Exam\UpdateExamRequest;
use App\Http\Resources\ExamAttemptDetailResource;
use App\Http\Resources\ExamAttemptResource;
use App\Http\Resources\ExamOptionResource;
use App\Http\Resources\ExamQuestionResource;
use App\Http\Resources\ExamResource;
use App\Models\CourseSession;
use App\Models\Exam;
use App\Models\ExamAttempt;
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
                    __('errors.prereq_session_not_met'),
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
        $totalQuestions = (int) $exam->questions()->count();

        $attempts = $exam->attempts()
            ->with(['user.roles', 'user.avatar'])
            ->withCount(['answers as correct_count' => fn ($q) => $q->where('is_correct', true)])
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 20));

        $attempts->getCollection()->each(function ($attempt) use ($totalQuestions) {
            $attempt->setAttribute('total_questions', $totalQuestions);
        });

        return ApiResponse::success(ExamAttemptResource::collection($attempts));
    }

    public function showAttempt(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $attempt->load([
            'answers',
            'user.roles',
            'user.avatar',
            'exam.session.course.term',
            'exam.questions.options',
        ]);

        return ApiResponse::success(new ExamAttemptDetailResource($attempt));
    }

    public function start(Request $request, Exam $exam): JsonResponse
    {
        $this->authorize('submit', $exam);

        try {
            $attempt = $this->scoringService->start($request->user(), $exam);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success(new ExamAttemptResource($attempt), __('messages.exam_started'), 201);
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

        return ApiResponse::success(new ExamAttemptResource($attempt), __('messages.exam_submitted'), 201);
    }

    public function store(StoreExamRequest $request): JsonResponse
    {
        $exam = $this->managementService->createExam($request->validated());

        return ApiResponse::success(new ExamResource($exam), __('messages.exam_created'), 201);
    }

    public function update(UpdateExamRequest $request, Exam $exam): JsonResponse
    {
        $updated = $this->managementService->updateExam($exam, $request->validated());

        return ApiResponse::success(new ExamResource($updated), __('messages.exam_updated'));
    }

    public function destroy(Exam $exam): JsonResponse
    {
        $this->authorize('delete', $exam);
        $this->managementService->deleteExam($exam);

        return ApiResponse::success(null, __('messages.exam_deleted'));
    }

    public function addQuestion(StoreQuestionRequest $request, Exam $exam): JsonResponse
    {
        try {
            $question = $this->managementService->addQuestion($exam, $request->validated());
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success(new ExamQuestionResource($question), __('messages.question_added'), 201);
    }

    public function addOption(StoreOptionRequest $request, ExamQuestion $question): JsonResponse
    {
        $option = $this->managementService->addOption($question, $request->validated());

        return ApiResponse::success(new ExamOptionResource($option), __('messages.option_added'), 201);
    }
}
