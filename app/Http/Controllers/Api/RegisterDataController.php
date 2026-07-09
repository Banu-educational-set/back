<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterData\UpsertRegisterDataRequest;
use App\Services\RegisterDataService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterDataController extends Controller
{
    public function __construct(private readonly RegisterDataService $service) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->service->all($request->user()),
            __('messages.ok'),
        );
    }

    public function upsert(UpsertRegisterDataRequest $request): JsonResponse
    {
        $data = $this->service->upsertMany(
            $request->user(),
            $request->validated()['data'],
        );

        return ApiResponse::success($data, __('messages.register_data_saved'));
    }
}
