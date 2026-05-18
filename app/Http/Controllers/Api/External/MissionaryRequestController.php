<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use App\Http\Requests\Missionary\StoreExternalRequestRequest;
use App\Http\Resources\MissionaryRequestResource;
use App\Services\MissionaryRequestService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class MissionaryRequestController extends Controller
{
    public function __construct(private readonly MissionaryRequestService $service) {}

    public function store(StoreExternalRequestRequest $request): JsonResponse
    {
        $created = $this->service->createFromExternal($request->validated());

        return ApiResponse::success(
            new MissionaryRequestResource($created),
            'Missionary request received.',
            201,
        );
    }
}
