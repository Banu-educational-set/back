<?php

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use App\Enums\MarriageStatus;
use App\Enums\RoleName;
use App\Models\City;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'national_code' => ['nullable', 'string', 'digits:10', 'unique:users,national_code'],
            'password' => ['required', Password::min(8)],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::in(RoleName::values())],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'marriage_status' => ['nullable', 'string', Rule::in(MarriageStatus::values())],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'string', Rule::in(Gender::values())],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->filled('city_id') || ! $this->filled('province_id')) {
                return;
            }

            $belongs = City::where('id', $this->integer('city_id'))
                ->where('province_id', $this->integer('province_id'))
                ->exists();

            if (! $belongs) {
                $v->errors()->add('city_id', 'City does not belong to the given province.');
            }
        });
    }
}
