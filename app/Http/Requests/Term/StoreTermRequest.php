<?php

namespace App\Http\Requests\Term;

use App\Models\Term;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'score' => ['sometimes', 'integer', 'min:1'],
            'minimum_score' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // Defaults match the DB column defaults (Persian academic norm: 20/12).
            $score = $this->has('score') ? $this->integer('score') : 20;
            $min = $this->has('minimum_score') ? $this->integer('minimum_score') : 12;
            if ($score > 0 && $min > $score) {
                $v->errors()->add('minimum_score', 'minimum_score cannot exceed score.');
            }
        });
    }
}
