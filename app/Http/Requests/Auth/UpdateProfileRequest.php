<?php

namespace App\Http\Requests\Auth;

use App\Models\City;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['sometimes', 'confirmed', Password::min(8)],
            'avatar_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'province_id' => ['sometimes', 'nullable', 'integer', 'exists:provinces,id'],
            'city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->has('city_id') && ! $this->has('province_id')) {
                return;
            }

            $provinceId = $this->has('province_id')
                ? $this->input('province_id')
                : $this->user()->province_id;
            $cityId = $this->has('city_id')
                ? $this->input('city_id')
                : $this->user()->city_id;

            if ($cityId === null || $provinceId === null) {
                return;
            }

            $belongs = City::where('id', (int) $cityId)
                ->where('province_id', (int) $provinceId)
                ->exists();

            if (! $belongs) {
                $v->errors()->add('city_id', 'City does not belong to the given province.');
            }
        });
    }
}
