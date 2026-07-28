<?php

namespace App\Http\Resources;

use App\Models\Child;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A child as the guardian's own roster shows them. `access` comes from the
 * `child_guardian` pivot, so this resource is only ever built from a
 * relation loaded through the authenticated guardian.
 *
 * @mixin Child
 */
class ChildSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $classroom = $this->activeEnrollment()?->classroom;

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            // Their own child — never abbreviated by child_name_display.
            'display_name' => $this->fullName(),
            'photo_url' => $this->photoUrl(),
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'age_string' => $this->ageString($this->center?->now()),
            'gender' => $this->gender?->value,
            'center_id' => $this->center_id,
            'classroom' => $classroom ? [
                'id' => $classroom->id,
                'name' => $classroom->name,
            ] : null,
            'access' => [
                'is_account_admin' => (bool) $this->pivot?->is_account_admin,
                'has_full_photo_access' => (bool) ($this->pivot?->has_full_photo_access ?? true),
            ],
        ];
    }
}
