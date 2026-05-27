<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class UserService
{
    public function paginate(?string $search, int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles', 'avatar'])
            ->when($search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        $user = User::create(Arr::only($data, ['name', 'email', 'phone', 'password']));

        if (! empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user->load('roles');
    }

    public function update(User $user, array $data): User
    {
        $user->fill(Arr::only($data, ['name', 'email', 'phone', 'password']))->save();

        if (array_key_exists('roles', $data)) {
            $user->syncRoles($data['roles']);
        }

        return $user->load('roles');
    }

    public function delete(User $user): void
    {
        $user->tokens()->delete();
        $user->delete();
    }
}
