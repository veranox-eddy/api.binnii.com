<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public const array TOGGLES = [
        'report_started', 'report_ready', 'new_entry',
        'new_photo', 'new_comment', 'classroom_photos',
    ];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_fill_keys(self::TOGGLES, ['boolean']);
    }
}
