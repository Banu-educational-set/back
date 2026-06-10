<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class UserService
{
    /**
     * @param  array<int, string>|null  $roles
     */
    public function paginate(?string $search, ?array $roles, int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles', 'avatar', 'province', 'city'])
            ->when($search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }))
            ->when($roles, fn ($q, $r) => $q->whereHas('roles', fn ($rq) => $rq->whereIn('name', $r)))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        $user = User::create(array_merge(
            Arr::only($data, [
                'name', 'email', 'phone', 'national_code', 'password', 'province_id', 'city_id',
                'marriage_status', 'birthday', 'gender', 'address', 'bio',
            ]),
            // Admin-created users skip the verification flow and start approved.
            ['status' => $data['status'] ?? UserStatus::Approved->value],
        ));

        if (! empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user->load(['roles', 'province', 'city']);
    }

    public function update(User $user, array $data): User
    {
        $user->fill(Arr::only($data, [
            'name', 'email', 'phone', 'national_code', 'password', 'province_id', 'city_id',
            'marriage_status', 'birthday', 'gender', 'address', 'bio',
        ]))->save();

        if (array_key_exists('roles', $data)) {
            $user->syncRoles($data['roles']);
        }

        return $user->load(['roles', 'province', 'city']);
    }

    public function delete(User $user): void
    {
        $user->tokens()->delete();
        $user->delete();
    }
}
