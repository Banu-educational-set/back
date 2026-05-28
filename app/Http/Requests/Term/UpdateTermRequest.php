<?php

namespace App\Http\Requests\Term;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('term')) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
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
            if (! $this->has('score') && ! $this->has('minimum_score')) {
                return;
            }
            /** @var \App\Models\Term|null $term */
            $term = $this->route('term');
            $score = $this->has('score') ? $this->integer('score') : (int) ($term?->score ?? 0);
            $min = $this->has('minimum_score') ? $this->integer('minimum_score') : (int) ($term?->minimum_score ?? 0);
            if ($score > 0 && $min > $score) {
                $v->errors()->add('minimum_score', 'minimum_score cannot exceed score.');
            }
        });
    }
}
