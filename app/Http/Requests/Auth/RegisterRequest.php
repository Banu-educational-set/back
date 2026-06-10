<?php

namespace App\Http\Requests\Auth;

use App\Enums\Gender;
use App\Enums\MarriageStatus;
use App\Models\City;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'national_code' => ['nullable', 'string', 'digits:10', 'unique:users,national_code'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'marriage_status' => ['nullable', 'string', Rule::in(MarriageStatus::values())],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', 'string', Rule::in(Gender::values())],
            'address' => ['nullable', 'string', 'max:1000'],
            'bio' => ['nullable', 'string', 'max:2000'],
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
