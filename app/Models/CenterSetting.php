<?php

namespace App\Models;

use App\Enums\AttendanceTimeFormat;
use App\Enums\ChildNameDisplay;
use App\Enums\SignInIdentification;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'center_id', 'phone_visible_on_app', 'delayed_media_hours', 'auto_send_report_time',
    'attendance_time_format', 'teacher_editable_timecards', 'sign_in_identification',
    'child_name_display', 'parents_can_sign_in', 'safe_pickup', 'checkin_zone_enabled',
    'sign_in_code_enabled', 'classroom_access', 'parents_can_mark_absent',
    'show_waitlist_position',
    'is_full_week_center', 'curriculum_enabled', 'curriculum_trial_ends_on',
    'staff_management_enabled', 'smart_billing_enabled',
])]
class CenterSetting extends Model
{
    /**
     * Mirror the migration defaults so an unsaved instance renders the
     * configuration form correctly for a center without a settings row.
     */
    protected $attributes = [
        'phone_visible_on_app' => false,
        'delayed_media_hours' => 0,
        'attendance_time_format' => '12h',
        'teacher_editable_timecards' => false,
        'sign_in_identification' => 'none',
        'child_name_display' => 'full_last',
        'parents_can_sign_in' => false,
        'safe_pickup' => false,
        'checkin_zone_enabled' => false,
        'sign_in_code_enabled' => false,
        'classroom_access' => false,
        'parents_can_mark_absent' => false,
        'show_waitlist_position' => false,
        'is_full_week_center' => false,
        'curriculum_enabled' => false,
        'staff_management_enabled' => false,
        'smart_billing_enabled' => false,
    ];

    protected function casts(): array
    {
        return [
            'phone_visible_on_app' => 'boolean',
            'delayed_media_hours' => 'integer',
            'attendance_time_format' => AttendanceTimeFormat::class,
            'teacher_editable_timecards' => 'boolean',
            'sign_in_identification' => SignInIdentification::class,
            'child_name_display' => ChildNameDisplay::class,
            'parents_can_sign_in' => 'boolean',
            'safe_pickup' => 'boolean',
            'checkin_zone_enabled' => 'boolean',
            'sign_in_code_enabled' => 'boolean',
            'classroom_access' => 'boolean',
            'parents_can_mark_absent' => 'boolean',
            'show_waitlist_position' => 'boolean',
            'is_full_week_center' => 'boolean',
            'curriculum_enabled' => 'boolean',
            'curriculum_trial_ends_on' => 'date',
            'staff_management_enabled' => 'boolean',
            'smart_billing_enabled' => 'boolean',
        ];
    }

    /**
     * Start the 14-day Digital Curriculum trial the first time it is turned
     * on. Idempotent on purpose: switching curriculum off and on again must
     * not hand out a fresh trial.
     */
    public function startCurriculumTrial(Carbon $today): void
    {
        if (! $this->curriculum_trial_ends_on) {
            $this->curriculum_trial_ends_on = $today->copy()->addDays(14)->toDateString();
        }
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
