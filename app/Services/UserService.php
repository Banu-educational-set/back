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
     * @param  array<string, mixed>  $filters  name, national_code, phone, role, status, from_date, to_date
     */
    public function paginate(?string $search, ?array $roles, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles', 'avatar', 'province', 'city'])
            ->when($search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }))
            ->when($roles, fn ($q, $r) => $q->whereHas('roles', fn ($rq) => $rq->whereIn('name', $r)))
            ->when($filters['name'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($filters['national_code'] ?? null, fn ($q, $v) => $q->where('national_code', 'like', "%{$v}%"))
            ->when($filters['phone'] ?? null, fn ($q, $v) => $q->where('phone', 'like', "%{$v}%"))
            ->when($filters['role'] ?? null, fn ($q, $v) => $q->whereHas('roles', fn ($rq) => $rq->where('name', $v)))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['from_date'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to_date'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
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
