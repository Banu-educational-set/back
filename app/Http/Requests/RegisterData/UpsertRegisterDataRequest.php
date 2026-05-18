<?php

namespace App\Http\Requests\RegisterData;

use Illuminate\Foundation\Http\FormRequest;

class UpsertRegisterDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'array', 'min:1'],
            'data.*' => ['present'],
        ];
    }
}
