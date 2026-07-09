<?php

namespace App\Http\Requests\Missionary;

use App\Enums\MissionaryRequestStatus;
use App\Models\MissionaryRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMemoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            // Optional link to one of the missionary's own accepted requests.
            'missionary_request_id' => ['nullable', 'integer', 'exists:missionary_requests,id'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->filled('missionary_request_id')) {
                return;
            }

            $mr = MissionaryRequest::find($this->integer('missionary_request_id'));

            if (! $mr
                || $mr->missionary_id !== $this->user()->id
                || $mr->status !== MissionaryRequestStatus::Accepted) {
                $v->errors()->add('missionary_request_id', __('errors.memory_request_not_accepted'));
            }
        });
    }
}
