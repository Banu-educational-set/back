<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $purposes = array_keys(config('education.media.purposes', []));

        return [
            'purpose' => ['required', 'string', Rule::in($purposes)],
            'file' => ['required', 'file'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $purpose = (string) $this->input('purpose', '');
            $config = config("education.media.purposes.{$purpose}");

            if (! is_array($config)) {
                return;
            }

            $file = $this->file('file');
            if (! $file || ! $file->isValid()) {
                return;
            }

            $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: '');
            if (! in_array($ext, $config['allowed_mimes'], true)) {
                $v->errors()->add('file', sprintf(
                    'File extension must be one of: %s.',
                    implode(', ', $config['allowed_mimes']),
                ));
            }

            $maxKb = (int) $config['max_size_kb'];
            if ($file->getSize() > $maxKb * 1024) {
                $v->errors()->add('file', "File exceeds {$maxKb} KB.");
            }
        });
    }
}
