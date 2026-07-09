<?php

namespace App\Http\Controllers\Api\Missionary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Missionary\UpdateRequestStatusRequest;
use App\Http\Resources\MissionaryRequestResource;
use App\Models\MissionaryRequest;
use App\Services\MissionaryRequestService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class RequestController extends Controller
{
    public function __construct(private readonly MissionaryRequestService $service) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            MissionaryRequestResource::collection(
                $this->service->listForMissionary($request->user()),
            ),
        );
    }

    public function show(Request $request, MissionaryRequest $missionaryRequest): JsonResponse
    {
        if ($missionaryRequest->missionary_id !== null
            && $missionaryRequest->missionary_id !== $request->user()->id) {
            return ApiResponse::error(__('errors.forbidden'), null, 403);
        }

        return ApiResponse::success(new MissionaryRequestResource($missionaryRequest));
    }

    public function updateStatus(UpdateRequestStatusRequest $request, MissionaryRequest $missionaryRequest): JsonResponse
    {
        try {
            $updated = $this->service->updateStatus(
                $request->user(),
                $missionaryRequest,
                $request->validated('status'),
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, 403);
        }

        return ApiResponse::success(new MissionaryRequestResource($updated), __('messages.status_updated'));
    }
}
