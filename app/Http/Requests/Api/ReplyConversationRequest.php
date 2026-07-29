<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

class ReplyConversationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
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

            if ($total > StoreConversationRequest::MAX_TOTAL_UPLOAD) {
                $validator->errors()->add('attachments', 'Cumulative max file upload size: 25mb.');
            }
        });
    }
}
