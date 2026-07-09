<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMissionaryRequestStatusRequest;
use App\Http\Resources\MissionaryRequestResource;
use App\Models\MissionaryRequest;
use App\Services\MissionaryRequestService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MissionaryRequestController extends Controller
{
    public function __construct(private readonly MissionaryRequestService $service) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            MissionaryRequestResource::collection(
                $this->service->listAll((int) $request->integer('per_page', 20)),
            ),
        );
    }

    public function show(MissionaryRequest $missionaryRequest): JsonResponse
    {
        return ApiResponse::success(
            new MissionaryRequestResource($missionaryRequest->load('missionary')),
        );
    }

    public function updateStatus(
        UpdateMissionaryRequestStatusRequest $request,
        MissionaryRequest $missionaryRequest,
    ): JsonResponse {
        $updated = $this->service->updateStatusByAdmin(
            $missionaryRequest,
            $request->validated('status'),
        );

        return ApiResponse::success(
            new MissionaryRequestResource($updated),
            __('messages.status_updated'),
        );
    }
}
