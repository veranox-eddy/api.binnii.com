<?php

namespace App\Http\Resources;

use App\Models\Center;
use App\Models\CenterSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Only the handful of center settings the SPA branches on. Everything else
 * on `center_settings` is admin configuration and stays out of the parent
 * API.
 *
 * @mixin Center
 */
class CenterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // A center without a settings row still has to answer: the model's
        // $attributes carry the same defaults as the migration.
        $settings = $this->settings ?? new CenterSetting;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'timezone' => $this->timezone,
            'settings' => [
                'delayed_media_hours' => (int) $settings->delayed_media_hours,
                'child_name_display' => $settings->child_name_display?->value,
                'parents_can_mark_absent' => (bool) $settings->parents_can_mark_absent,
            ],
        ];
    }
}
