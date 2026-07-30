<?php

namespace App\Http\Requests\Admin;

use App\Enums\Gender;
use App\Enums\MarriageStatus;
use App\Enums\RoleName;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Phone is the login/verification identifier (email is optional and
            // never used for auth), so it's the required unique field here.
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32', 'unique:users,phone'],
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
            'bio' => ['nullable', 'string', 'max:2000'],
            'avatar_media_id' => [
                'nullable',
                'integer',
                Rule::exists('media', 'id')->where(fn ($query) => $query
                    ->where('collection_name', 'avatar')
                    ->where('uploaded_by', $this->user()?->id)
                    ->whereNull('model_id')),
            ],
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
                $v->errors()->add('city_id', __('errors.city_not_in_province'));
            }
        });
    }
}
