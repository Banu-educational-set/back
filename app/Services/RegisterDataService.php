<?php

namespace App\Services;

use App\Models\RegisterData;
use App\Models\User;

class RegisterDataService
{
    /**
     * Return the user's register data as a flat key => value associative array.
     *
     * @return array<string, mixed>
     */
    public function all(User $user): array
    {
        return $user->registerData()
            ->get(['key', 'value'])
            ->mapWithKeys(fn (RegisterData $row) => [$row->key => $row->value])
            ->all();
    }

    /**
     * Upsert each key/value pair for the user. Existing keys are updated;
     * new keys are inserted. Returns the full set after the write.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function upsertMany(User $user, array $data): array
    {
        foreach ($data as $key => $value) {
            RegisterData::updateOrCreate(
                ['user_id' => $user->id, 'key' => (string) $key],
                ['value' => $value],
            );
        }

        return $this->all($user);
    }
}
