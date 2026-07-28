<?php

namespace App\Http\Resources;

use App\Models\Child;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The full child profile (S04/S05).
 *
 * `center_email` is deliberately absent: it belongs to API_02 M6, the
 * email-to-journal address, which is not part of v1. The SPA hides the
 * Profile email field when the key is missing — adding the column later is
 * what should bring the key back.
 *
 * @mixin Child
 */
class ChildResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $classroom = $this->activeEnrollment()?->classroom;
        $pivot = $this->pivot;

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->displayName($this->center?->settings?->child_name_display),
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'birthday_formatted' => $this->date_of_birth?->format('M j, Y'),
            'age_string' => $this->ageString($this->center?->now()),
            'gender' => $this->gender?->value,
            'photo_url' => $this->photoUrl(),
            'photo_consent' => (bool) $this->photo_consent,
            'classroom' => $classroom ? [
                'id' => $classroom->id,
                'name' => $classroom->name,
            ] : null,
            'my_relationship' => [
                'type' => $pivot?->type,
                'relationship' => $pivot?->relationship,
                'nickname' => $pivot?->nickname,
            ],
            'access' => [
                'is_account_admin' => (bool) $pivot?->is_account_admin,
                'has_full_photo_access' => (bool) ($pivot?->has_full_photo_access ?? true),
            ],
        ];
    }
}
