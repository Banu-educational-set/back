<?php

namespace App\Http\Controllers\Api\Missionary;

use App\Http\Controllers\Controller;
use App\Services\MissionaryDashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly MissionaryDashboardService $service) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($this->service->build($request->user()));
    }
}
