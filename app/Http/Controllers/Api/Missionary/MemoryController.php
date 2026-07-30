<?php

namespace App\Http\Controllers\Api\Missionary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Missionary\StoreMemoryRequest;
use App\Http\Requests\Missionary\UpdateMemoryRequest;
use App\Http\Resources\MissionaryMemoryResource;
use App\Models\MissionaryMemory;
use App\Services\MissionaryMemoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemoryController extends Controller
{
    public function __construct(private readonly MissionaryMemoryService $service) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            MissionaryMemoryResource::collection(
                $this->service->listForMissionary(
                    $request->user(),
                    (int) $request->integer('per_page', 20),
                    $request->only(['title', 'missionary_request_id', 'from_date', 'to_date']),
                ),
            ),
        );
    }

    public function store(StoreMemoryRequest $request): JsonResponse
    {
        $memory = $this->service->create($request->user(), $request->validated());

        return ApiResponse::success(
            new MissionaryMemoryResource($memory),
            __('messages.memory_created'),
            201,
        );
    }

    public function show(Request $request, MissionaryMemory $memory): JsonResponse
    {
        if ($memory->missionary_id !== $request->user()->id) {
            return ApiResponse::error(__('errors.forbidden'), null, 403);
        }

        return ApiResponse::success(
            new MissionaryMemoryResource($memory->load(['media', 'missionaryRequest'])),
        );
    }

    public function update(UpdateMemoryRequest $request, MissionaryMemory $memory): JsonResponse
    {
        if ($memory->missionary_id !== $request->user()->id) {
            return ApiResponse::error(__('errors.forbidden'), null, 403);
        }

        $updated = $this->service->update($memory, $request->user(), $request->validated());

        return ApiResponse::success(
            new MissionaryMemoryResource($updated),
            __('messages.memory_updated'),
        );
    }

    public function destroy(Request $request, MissionaryMemory $memory): JsonResponse
    {
        if ($memory->missionary_id !== $request->user()->id) {
            return ApiResponse::error(__('errors.forbidden'), null, 403);
        }

        $this->service->delete($memory);

        return ApiResponse::success(null, __('messages.memory_deleted'));
    }
}
