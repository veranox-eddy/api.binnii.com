<?php

namespace App\Models;

use App\Enums\ApplicationContactType;
use App\Enums\ApplicationStage;
use App\Enums\ApplicationStatus;
use App\Enums\ChildGender;
use App\Enums\EnrollmentStatus;
use App\Enums\PreferredTimeOfDay;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'center_id', 'child_id', 'child_first_name', 'child_last_name', 'date_of_birth',
    'gender', 'address_line1', 'address_line2', 'city', 'state', 'country', 'zip',
    'classroom_id', 'preferred_start_date', 'preferred_time_of_day', 'preferred_weekly_days',
    'subsidy_flag', 'priority', 'internal_notes', 'stage', 'status', 'submitted_at',
    'invite_sent_at',
])]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'gender' => ChildGender::class,
            'preferred_start_date' => 'date',
            'preferred_time_of_day' => PreferredTimeOfDay::class,
            'preferred_weekly_days' => 'array',
            'subsidy_flag' => 'boolean',
            'priority' => 'integer',
            'stage' => ApplicationStage::class,
            'status' => ApplicationStatus::class,
            'submitted_at' => 'datetime',
            'invite_sent_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ApplicationContact::class);
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(ApplicationAllergy::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ApplicationResponse::class);
    }

    public function childName(): string
    {
        return trim("{$this->child_first_name} {$this->child_last_name}");
    }

    /**
     * Fields children require but this application is missing — enrollment
     * is blocked until they are filled (no fabricated demographics).
     *
     * @return array<int, string>
     */
    public function missingEnrollmentFields(): array
    {
        return array_keys(array_filter([
            'date of birth' => $this->date_of_birth === null,
            'gender' => $this->gender === null,
        ]));
    }

    /**
     * Enroll: create the real child from this application, reusing the
     * Phase 4 Child sync methods (guardians shared by email, allergies) —
     * no duplicated child logic. Links child_id and closes the funnel.
     */
    public function convertToChild(): Child
    {
        if ($this->child_id) {
            return $this->child;
        }

        if ($this->missingEnrollmentFields() !== []) {
            throw new \LogicException('Application is missing required child fields; guard with missingEnrollmentFields() first.');
        }

        return DB::transaction(function (): Child {
            $child = Child::create([
                'center_id' => $this->center_id,
                'first_name' => $this->child_first_name,
                'last_name' => $this->child_last_name,
                'date_of_birth' => $this->date_of_birth->toDateString(),
                'gender' => $this->gender,
                'is_subsidized' => $this->subsidy_flag,
                'address_line1' => $this->address_line1,
                'address_line2' => $this->address_line2,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'zip' => $this->zip,
            ]);

            if ($this->classroom_id) {
                $startsInFuture = $this->preferred_start_date?->isFuture() ?? false;
                $child->syncEnrollments([[
                    'classroom_id' => $this->classroom_id,
                    'status' => $startsInFuture ? EnrollmentStatus::Upcoming->value : EnrollmentStatus::Active->value,
                    'enrolled_on' => $this->preferred_start_date?->toDateString(),
                    'days' => $this->preferred_weekly_days ?? [],
                ]]);
            }

            $child->syncContacts(
                $this->contacts->where('type', ApplicationContactType::Guardian)->map(fn ($c) => [
                    'name' => trim("{$c->first_name} {$c->last_name}"),
                    'email' => $c->email,
                    'mobile_phone' => $c->phone,
                    'type' => 'parent',
                ])->values()->all(),
                $this->contacts->where('type', ApplicationContactType::Emergency)->map(fn ($c) => [
                    'name' => trim("{$c->first_name} {$c->last_name}"),
                    'mobile_phone' => $c->phone,
                    'relationship' => $c->relationship,
                ])->values()->all(),
            );

            $child->syncAllergies($this->allergies->map(fn ($a) => [
                'note' => $a->note,
                'severity' => $a->severity->value,
            ])->values()->all());

            $this->update([
                'child_id' => $child->id,
                'stage' => ApplicationStage::Enrolled,
                'status' => ApplicationStatus::Enrolled,
            ]);

            return $child;
        });
    }
}
