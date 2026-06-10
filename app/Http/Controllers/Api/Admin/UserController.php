<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Enums\UserStatus;
use App\Http\Requests\Admin\AssignRoleRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $rolesInput = $request->input('roles');
        if (is_string($rolesInput)) {
            $rolesInput = array_filter(array_map('trim', explode(',', $rolesInput)));
        }
        $roles = is_array($rolesInput) ? array_values(array_unique($rolesInput)) : null;

        if ($roles !== null) {
            $invalid = array_values(array_diff($roles, RoleName::values()));
            if ($invalid !== []) {
                return ApiResponse::error(
                    'Invalid role(s): '.implode(', ', $invalid).'. Allowed: '.implode(', ', RoleName::values()).'.',
                    ['roles' => ['Invalid value(s).']],
                    422,
                );
            }
            if ($roles === []) {
                $roles = null;
            }
        }

        $users = $this->userService->paginate(
            search: $request->string('search')->toString() ?: null,
            roles: $roles,
            perPage: (int) $request->integer('per_page', 20),
        );

        return ApiResponse::success(UserResource::collection($users));
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return ApiResponse::success(new UserResource($user->load(['roles', 'avatar', 'province', 'city'])));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return ApiResponse::success(new UserResource($user), 'User created.', 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $updated = $this->userService->update($user, $request->validated());

        return ApiResponse::success(new UserResource($updated), 'User updated.');
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user);

        return ApiResponse::success(null, 'User deleted.');
    }

    public function assignRole(AssignRoleRequest $request, User $user): JsonResponse
    {
        $updated = $this->userService->update($user, ['roles' => $request->validated('roles')]);

        return ApiResponse::success(new UserResource($updated), 'Roles updated.');
    }

    public function setStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        $status = UserStatus::from($request->validated('status'));
        $user->forceFill(['status' => $status->value])->save();

        // Blocking should kill the user's outstanding sessions immediately.
        if ($status === UserStatus::Blocked) {
            $user->tokens()->delete();
        }

        return ApiResponse::success(
            new UserResource($user->fresh(['roles', 'avatar', 'province', 'city'])),
            'User status updated.',
        );
    }
}
