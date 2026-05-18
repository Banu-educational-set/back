<?php

namespace App\Http\Requests\Missionary;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExternalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'missionary_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'external_source' => ['nullable', 'string', 'max:64'],
            'external_reference_id' => ['nullable', 'string', 'max:128'],
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_phone' => ['nullable', 'string', 'max:32'],
            'requester_email' => ['nullable', 'email', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'requested_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->filled('missionary_id')) {
                return;
            }
            $missionary = \App\Models\User::find($this->integer('missionary_id'));
            if (! $missionary || ! $missionary->hasRole(RoleName::Missionary->value)) {
                $v->errors()->add('missionary_id', 'missionary_id must reference a user with the missionary role.');
            }
        });
    }
}
