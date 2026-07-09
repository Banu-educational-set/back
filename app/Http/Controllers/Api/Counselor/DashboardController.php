<?php

namespace App\Http\Controllers\Api\Counselor;

use App\Http\Controllers\Controller;
use App\Services\CounselorDashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly CounselorDashboardService $service) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($this->service->build($request->user()));
    }
}
