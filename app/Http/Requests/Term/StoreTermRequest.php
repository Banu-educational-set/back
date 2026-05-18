<?php

namespace App\Http\Requests\Term;

use App\Models\Term;
use Illuminate\Foundation\Http\FormRequest;

class StoreTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Term::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }
}
