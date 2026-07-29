<?php

namespace App\Http\Resources;

use App\Models\Guardian;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of a child's Crew (S12/S13): the guardian plus their
 * `child_guardian` pivot. Must be built from `$child->guardians()` so the
 * pivot is present.
 *
 * @mixin Guardian
 */
class CrewMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'guardian_id' => $this->id,
            'name' => $this->fullName(),
            'email' => $this->email,
            'relationship' => $this->pivot->relationship,
            'type' => $this->pivot->type,
            'is_account_admin' => (bool) $this->pivot->is_account_admin,
            'has_full_photo_access' => (bool) $this->pivot->has_full_photo_access,
            'nickname' => $this->pivot->nickname,
            'registration_status' => $this->registration_status->value,
        ];
    }
}
