<?php

use App\Enums\RoleName;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Api\Admin\MissionaryRequestController as AdminMissionaryRequestController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseSessionController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\External\MissionaryController as ExternalMissionaryController;
use App\Http\Controllers\Api\External\MissionaryRequestController as ExternalMissionaryRequestController;
use App\Http\Controllers\Api\HomeworkController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\Counselor\DashboardController as CounselorDashboardController;
use App\Http\Controllers\Api\Missionary\DashboardController as MissionaryDashboardController;
use App\Http\Controllers\Api\Missionary\MemoryController as MissionaryMemoryController;
use App\Http\Controllers\Api\Missionary\PassedController as MissionaryPassedController;
use App\Http\Controllers\Api\Missionary\RequestController as MissionaryRequestController;
use App\Http\Controllers\Api\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Api\RegisterDataController;
use App\Http\Controllers\Api\Staff\TicketController as StaffTicketController;
use App\Http\Controllers\Api\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Api\Student\EnrollmentController;
use App\Http\Controllers\Api\Student\ResultsController as StudentResultsController;
use App\Http\Controllers\Api\Student\TicketController as StudentTicketController;
use App\Http\Controllers\Api\TermController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json(['success' => true, 'message' => 'pong', 'data' => null]));

/*
 * Locations (public — needed by the registration form)
 */
Route::get('provinces', [LocationController::class, 'provinces']);
Route::get('provinces/{province}/cities', [LocationController::class, 'cities']);

/*
 * Auth (public)
 */
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::post('login/otp/request', [AuthController::class, 'requestLoginOtp']);
    Route::post('login/otp/verify', [AuthController::class, 'verifyLoginOtp']);

    Route::post('password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('password/verify-otp', [AuthController::class, 'verifyPasswordResetOtp']);
    Route::post('password/reset', [AuthController::class, 'resetPassword']);
});

/*
 * External (no auth, secured by API key header)
 */
Route::middleware('external.api_key')->prefix('external')->group(function () {
    Route::post('missionary-requests', [ExternalMissionaryRequestController::class, 'store']);

    Route::get('missionaries', [ExternalMissionaryController::class, 'index']);
    Route::get('missionaries/{user}', [ExternalMissionaryController::class, 'show']);
});

/*
 * Authenticated (any role)
 */
Route::middleware(['auth:sanctum', 'approved'])->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    Route::match(['put', 'patch'], 'me', [AuthController::class, 'updateMe']);

    Route::get('me/register-data', [RegisterDataController::class, 'index']);
    Route::post('me/register-data', [RegisterDataController::class, 'upsert']);

    // Unified media upload — returns a media id which is then attached to an
    // entity via that entity's create/update endpoint (e.g. cover_media_id,
    // avatar_media_id, media_ids[]).
    Route::post('media', [MediaController::class, 'store']);

    // Read-only access for everyone
    Route::get('courses', [CourseController::class, 'index']);
    Route::get('courses/{course}', [CourseController::class, 'show']);
    Route::get('sessions', [CourseSessionController::class, 'index']);
    Route::get('sessions/{session}', [CourseSessionController::class, 'show']);
    Route::get('sessions/{session}/exams', [ExamController::class, 'forSession']);
    Route::get('sessions/{session}/homeworks', [HomeworkController::class, 'forSession']);
    Route::get('exams/{exam}', [ExamController::class, 'show']);
    Route::get('homeworks/{homework}', [HomeworkController::class, 'show']);

    // Authenticated download endpoint for files (works for all disks).
    Route::get('media/{medium}/download', [MediaController::class, 'download'])->name('media.download');
});

/*
 * Authoring routes — admin or teacher only
 */
Route::middleware([
    'auth:sanctum',
    'approved',
    'role:'.RoleName::Admin->value.'|'.RoleName::Teacher->value,
])->group(function () {
    Route::get('terms', [TermController::class, 'index']);
    Route::get('terms/{term}', [TermController::class, 'show']);
    Route::post('terms', [TermController::class, 'store']);
    Route::match(['put', 'patch'], 'terms/{term}', [TermController::class, 'update']);

    Route::post('courses', [CourseController::class, 'store']);
    Route::match(['put', 'patch'], 'courses/{course}', [CourseController::class, 'update']);

    Route::post('sessions', [CourseSessionController::class, 'store']);
    Route::match(['put', 'patch'], 'sessions/{session}', [CourseSessionController::class, 'update']);

    Route::post('exams', [ExamController::class, 'store']);
    Route::match(['put', 'patch'], 'exams/{exam}', [ExamController::class, 'update']);
    Route::post('exams/{exam}/questions', [ExamController::class, 'addQuestion']);
    Route::post('questions/{question}/options', [ExamController::class, 'addOption']);

    Route::post('homeworks', [HomeworkController::class, 'store']);
    Route::match(['put', 'patch'], 'homeworks/{homework}', [HomeworkController::class, 'update']);
    Route::patch('homework-submissions/{submission}/review', [HomeworkController::class, 'review']);

    Route::delete('media/{medium}', [MediaController::class, 'destroy']);

    Route::get('terms/{term}/attendees', [AttendanceController::class, 'term']);
    Route::get('courses/{course}/attendees', [AttendanceController::class, 'course']);
    Route::get('sessions/{session}/attendees', [AttendanceController::class, 'session']);

    Route::get('homeworks/{homework}/submissions', [HomeworkController::class, 'submissions']);
    Route::get('exams/{exam}/attempts', [ExamController::class, 'attempts']);
    Route::get('exam-attempts/{attempt}', [ExamController::class, 'showAttempt']);
});

/*
 * Admin-only delete (terms/courses/sessions/exams/homeworks)
 */
Route::middleware(['auth:sanctum', 'approved', 'role:'.RoleName::Admin->value])->group(function () {
    Route::delete('terms/{term}', [TermController::class, 'destroy']);
    Route::delete('courses/{course}', [CourseController::class, 'destroy']);
    Route::delete('sessions/{session}', [CourseSessionController::class, 'destroy']);
    Route::delete('exams/{exam}', [ExamController::class, 'destroy']);
    Route::delete('homeworks/{homework}', [HomeworkController::class, 'destroy']);
});

/*
 * Student / Missionary scope
 */
Route::middleware([
    'auth:sanctum',
    'approved',
    'role:'.RoleName::Student->value.'|'.RoleName::Missionary->value,
])->group(function () {
    Route::post('exams/{exam}/start', [ExamController::class, 'start']);
    Route::post('exams/{exam}/submit', [ExamController::class, 'submit']);
    Route::post('homeworks/{homework}/submit', [HomeworkController::class, 'submit']);

    Route::prefix('student')->group(function () {
        Route::get('dashboard', [StudentDashboardController::class, 'index']);

        Route::get('my-terms', [EnrollmentController::class, 'myTerms']);
        Route::get('my-terms/{term}', [EnrollmentController::class, 'showTerm']);

        Route::get('exam-results', [StudentResultsController::class, 'examResults']);
        Route::get('exams', [StudentResultsController::class, 'exams']);
        Route::get('homeworks', [StudentResultsController::class, 'homeworks']);

        Route::get('tickets', [StudentTicketController::class, 'index']);
        Route::get('tickets/stats', [StudentTicketController::class, 'stats']);
        Route::post('tickets', [StudentTicketController::class, 'store']);
        Route::get('tickets/{ticket}', [StudentTicketController::class, 'show']);
        Route::post('tickets/{ticket}/messages', [StudentTicketController::class, 'postMessage']);
    });
});

/*
 * Admin
 */
Route::middleware(['auth:sanctum', 'approved', 'role:'.RoleName::Admin->value])
    ->prefix('admin')
    ->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index']);

        Route::apiResource('users', AdminUserController::class);
        Route::patch('users/{user}/role', [AdminUserController::class, 'assignRole']);
        Route::patch('users/{user}/status', [AdminUserController::class, 'setStatus']);
        Route::get('users/{user}/register-data', [AdminUserController::class, 'registerData']);

        Route::get('homeworks', [HomeworkController::class, 'index']);
        Route::get('exams', [ExamController::class, 'index']);

        // Admin-prefixed list aliases (same controllers as the unprefixed routes)
        // so the frontend's /admin/* list URLs resolve with all filter params.
        Route::get('terms', [TermController::class, 'index']);
        Route::get('courses', [CourseController::class, 'index']);
        Route::get('sessions', [CourseSessionController::class, 'index']);
        Route::get('assignments', [HomeworkController::class, 'index']);

        Route::get('terms/{term}/enrollees', [AdminEnrollmentController::class, 'index']);
        Route::post('terms/{term}/enrollees', [AdminEnrollmentController::class, 'store']);

        Route::get('tickets', [StaffTicketController::class, 'index']);
        Route::get('tickets/stats', [StaffTicketController::class, 'stats']);
        Route::get('tickets/{ticket}', [StaffTicketController::class, 'show']);
        Route::post('tickets/{ticket}/messages', [StaffTicketController::class, 'postMessage']);
        Route::patch('tickets/{ticket}/status', [StaffTicketController::class, 'updateStatus']);

        Route::get('missionary-requests', [AdminMissionaryRequestController::class, 'index']);
        Route::get('missionary-requests/{missionaryRequest}', [AdminMissionaryRequestController::class, 'show']);
        Route::patch('missionary-requests/{missionaryRequest}/status', [AdminMissionaryRequestController::class, 'updateStatus']);
    });

/*
 * Counselor
 */
Route::middleware(['auth:sanctum', 'approved', 'role:'.RoleName::Counselor->value])
    ->prefix('counselor')
    ->group(function () {
        Route::get('dashboard', [CounselorDashboardController::class, 'index']);

        Route::get('tickets', [StaffTicketController::class, 'index']);
        Route::get('tickets/{ticket}', [StaffTicketController::class, 'show']);
        Route::post('tickets/{ticket}/messages', [StaffTicketController::class, 'postMessage']);
        Route::patch('tickets/{ticket}/status', [StaffTicketController::class, 'updateStatus']);
    });

/*
 * Teacher
 */
Route::middleware(['auth:sanctum', 'approved', 'role:'.RoleName::Teacher->value])
    ->prefix('teacher')
    ->group(function () {
        Route::get('dashboard', [TeacherDashboardController::class, 'index']);
    });

/*
 * Missionary
 */
Route::middleware(['auth:sanctum', 'approved', 'role:'.RoleName::Missionary->value])
    ->prefix('missionary')
    ->group(function () {
        Route::get('dashboard', [MissionaryDashboardController::class, 'index']);

        // Passed content — same shapes as the student term/course/session views,
        // scoped to the missionary's completed (passed) terms.
        Route::get('passed-terms', [MissionaryPassedController::class, 'terms']);
        Route::get('passed-courses', [MissionaryPassedController::class, 'courses']);
        Route::get('passed-sessions', [MissionaryPassedController::class, 'sessions']);

        Route::get('requests', [MissionaryRequestController::class, 'index']);
        Route::get('requests/{missionaryRequest}', [MissionaryRequestController::class, 'show']);
        Route::patch('requests/{missionaryRequest}/status', [MissionaryRequestController::class, 'updateStatus']);

        Route::get('memories', [MissionaryMemoryController::class, 'index']);
        Route::post('memories', [MissionaryMemoryController::class, 'store']);
        Route::get('memories/{memory}', [MissionaryMemoryController::class, 'show']);
        Route::match(['put', 'patch'], 'memories/{memory}', [MissionaryMemoryController::class, 'update']);
        Route::delete('memories/{memory}', [MissionaryMemoryController::class, 'destroy']);
    });
