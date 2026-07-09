<?php

namespace App\Http\Controllers\Api\External;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Resources\External\MissionaryResource;
use App\Models\User;
use App\Services\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MissionaryController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $missionaries = $this->userService->paginate(
            search: $request->string('search')->toString() ?: null,
            roles: [RoleName::Missionary->value],
            perPage: (int) $request->integer('per_page', 20),
        );

        return ApiResponse::success(MissionaryResource::collection($missionaries));
    }

    public function show(User $user): JsonResponse
    {
        if (! $user->hasRole(RoleName::Missionary->value)) {
            return ApiResponse::error(__('errors.missionary_not_found'), null, 404);
        }

        return ApiResponse::success(
            new MissionaryResource($user->load([
                'roles', 'avatar', 'province', 'city', 'memories.media',
            ]))
        );
    }
}
