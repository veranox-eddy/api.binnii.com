<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreConversationRequest extends FormRequest
{
    /** Matches the admin compose form's cumulative cap. */
    public const int MAX_TOTAL_UPLOAD = 25 * 1024 * 1024;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'child_id' => ['required', 'integer', 'exists:children,id'],
            'send_to' => ['required', Rule::in(['director_teacher', 'director_only'])],
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:gif,jpeg,jpg,mov,mp4,pdf,png', 'max:25600'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $total = Collection::make($this->file('attachments', []))
                ->sum(fn ($file) => (int) $file->getSize());

            if ($total > self::MAX_TOTAL_UPLOAD) {
                $validator->errors()->add('attachments', 'Cumulative max file upload size: 25mb.');
            }
        });
    }
}
