<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Http\Resources\ProvinceResource;
use App\Models\Province;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function provinces(): JsonResponse
    {
        return ApiResponse::success(
            ProvinceResource::collection(Province::query()->orderBy('name')->get()),
        );
    }

    public function cities(Province $province): JsonResponse
    {
        return ApiResponse::success(
            CityResource::collection($province->cities()->orderBy('name')->get()),
        );
    }
}
