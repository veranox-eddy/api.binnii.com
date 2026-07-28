<?php

namespace App\Models;

use App\Enums\ChildGender;
use App\Enums\ChildGuardianType;
use App\Enums\ChildNameDisplay;
use App\Enums\EnrollmentStatus;
use Database\Factories\ChildFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'center_id', 'first_name', 'last_name', 'date_of_birth', 'gender', 'photo_path',
    'photo_consent', 'is_subsidized', 'address_line1', 'address_line2', 'city',
    'state', 'country', 'zip',
])]
class Child extends Model
{
    /** @use HasFactory<ChildFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'children';

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'gender' => ChildGender::class,
            'photo_consent' => 'boolean',
            'is_subsidized' => 'boolean',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(Allergy::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(ChildRecord::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ChildNote::class);
    }

    public function pickups(): HasMany
    {
        return $this->hasMany(ChildPickup::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'child_guardian')
            ->withPivot([
                'type', 'relationship', 'is_emergency', 'priority',
                'is_account_admin', 'has_full_photo_access', 'nickname',
            ]);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ChildMilestone::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ChildAttendance::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class);
    }

    public function subsidyAgreements(): HasMany
    {
        return $this->hasMany(SubsidyAgreement::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(BillingLedgerEntry::class);
    }

    public function healthLogs(): HasMany
    {
        return $this->hasMany(HealthLog::class);
    }

    public function sleepChecks(): HasMany
    {
        return $this->hasMany(SleepCheck::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * The name other families are allowed to see, per the center's
     * `child_name_display` setting ("Jane Doe" vs "Jane D."). Guardians see
     * their own children in full, so pass the setting only where the child
     * belongs to someone else.
     */
    public function displayName(?ChildNameDisplay $mode = null): string
    {
        return $mode === ChildNameDisplay::LastInitial
            ? trim($this->first_name.' '.(mb_substr($this->last_name, 0, 1) ? mb_substr($this->last_name, 0, 1).'.' : ''))
            : $this->fullName();
    }

    /** Public URL for the profile photo, or null when none is on file. */
    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    /**
     * Age like the roster column: "4y", "3y 7m". Pass the center's "now"
     * (Center::now()) so day-boundary ages render in the center's timezone.
     */
    public function ageString(?Carbon $at = null): string
    {
        $at ??= now();
        $years = (int) $this->date_of_birth->diffInYears($at);
        $months = (int) $this->date_of_birth->copy()->addYears($years)->diffInMonths($at);

        return $months > 0 ? "{$years}y {$months}m" : "{$years}y";
    }

    public function activeEnrollment(): ?Enrollment
    {
        return $this->enrollments->firstWhere('status', EnrollmentStatus::Active);
    }

    /**
     * Replace the enrollments (and their weekly days) with the submitted
     * rows; id-keyed so history (timestamps) survives edits.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function syncEnrollments(array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            if (blank($row['classroom_id'] ?? null)) {
                continue;
            }

            $data = [
                'classroom_id' => $row['classroom_id'],
                'status' => $row['status'],
                'rotation' => $row['rotation'] ?? null,
                'enrolled_on' => $row['enrolled_on'] ?? null,
                'graduated_on' => $row['graduated_on'] ?? null,
            ];

            $enrollment = isset($row['id']) ? $this->enrollments()->find($row['id']) : null;
            $enrollment ? $enrollment->update($data) : $enrollment = $this->enrollments()->create($data);

            $enrollment->syncDays(array_map(intval(...), $row['days'] ?? []));
            $keptIds[] = $enrollment->id;
        }

        foreach ($this->enrollments()->whereNotIn('id', $keptIds)->get() as $stale) {
            $stale->days()->delete();
            $stale->delete();
        }
    }

    /**
     * Sync parent/guardian and emergency contacts. Each row either updates
     * the guardian referenced by id, links an existing guardian matched by
     * email within the center (siblings share one record), or creates one.
     * "Name" is a single input in the wireframe — the last word becomes the
     * last name.
     *
     * @param  array<int, array<string, mixed>>  $contacts  parent/guardian rows in priority order
     * @param  array<int, array<string, mixed>>  $emergencies
     */
    public function syncContacts(array $contacts, array $emergencies): void
    {
        $links = [];
        $priority = 0;

        foreach ($contacts as $row) {
            if (blank($row['name'] ?? null)) {
                continue;
            }

            $guardian = $this->upsertGuardian($row);
            $links[$guardian->id] = [
                'type' => $row['type'] ?? ChildGuardianType::Parent->value,
                'relationship' => null,
                'is_emergency' => false,
                'priority' => ++$priority,
            ];
        }

        foreach ($emergencies as $row) {
            if (blank($row['name'] ?? null)) {
                continue;
            }

            $guardian = $this->upsertGuardian($row);

            if (isset($links[$guardian->id])) {
                $links[$guardian->id]['is_emergency'] = true;
                $links[$guardian->id]['relationship'] = $row['relationship'] ?? null;
            } else {
                $links[$guardian->id] = [
                    'type' => ChildGuardianType::Guardian->value,
                    'relationship' => $row['relationship'] ?? null,
                    'is_emergency' => true,
                    'priority' => null,
                ];
            }
        }

        $this->guardians()->sync($links);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function upsertGuardian(array $row): Guardian
    {
        [$firstName, $lastName] = Guardian::splitName($row['name']);

        $attributes = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $row['email'] ?? null,
            'mobile_country_code' => $row['mobile_country_code'] ?? null,
            'mobile_phone' => $row['mobile_phone'] ?? null,
            'home_phone' => $row['home_phone'] ?? null,
            'work_phone' => $row['work_phone'] ?? null,
        ];

        $guardian = null;

        if (filled($row['id'] ?? null)) {
            $guardian = Guardian::where('center_id', $this->center_id)->find($row['id']);
        }

        if (! $guardian && filled($row['email'] ?? null)) {
            $guardian = Guardian::where('center_id', $this->center_id)->where('email', $row['email'])->first();
        }

        if ($guardian) {
            // Guardians are shared across siblings: one child's form must not
            // blank fields the other child's family relies on — only filled
            // values overwrite. The name always updates as a pair since it is
            // the row's required anchor field.
            $guardian->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
                ...array_filter($attributes, fn ($value) => filled($value)),
            ]);

            return $guardian;
        }

        return Guardian::create([...$attributes, 'center_id' => $this->center_id]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function syncAllergies(array $rows): void
    {
        $this->syncChildRows('allergies', $rows, 'note', fn (array $row) => [
            'note' => $row['note'],
            'severity' => $row['severity'],
        ]);
    }

    /**
     * Records manage an uploaded file alongside the row: replaced uploads
     * and removed rows delete their file from the public disk.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function syncRecords(array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            if (blank($row['type'] ?? null)) {
                continue;
            }

            $data = [
                'type' => $row['type'],
                'label' => $row['label'] ?? null,
                'expiry_date' => ($row['no_expiry'] ?? false) ? null : ($row['expiry_date'] ?? null),
                'no_expiry' => (bool) ($row['no_expiry'] ?? false),
            ];

            $existing = isset($row['id']) ? $this->records()->find($row['id']) : null;

            // Only touch file_path when a new upload was stored for this row.
            if (filled($row['file_path'] ?? null)) {
                if ($existing?->file_path && $existing->file_path !== $row['file_path']) {
                    Storage::disk('public')->delete($existing->file_path);
                }
                $data['file_path'] = $row['file_path'];
            }

            if ($existing) {
                $existing->update($data);
                $keptIds[] = $existing->id;
            } else {
                $keptIds[] = $this->records()->create($data)->id;
            }
        }

        foreach ($this->records()->whereNotIn('id', $keptIds)->get() as $stale) {
            if ($stale->file_path) {
                Storage::disk('public')->delete($stale->file_path);
            }
            $stale->delete();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function syncNotes(array $rows): void
    {
        $this->syncChildRows('notes', $rows, 'body', fn (array $row) => [
            'category' => $row['category'],
            'body' => $row['body'],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function syncPickups(array $rows): void
    {
        $this->syncChildRows('pickups', $rows, 'name', fn (array $row) => [
            'name' => $row['name'],
            'phone' => $row['phone'] ?? null,
            'details' => $row['details'] ?? null,
        ]);
    }

    /**
     * Shared id-keyed upsert for the simple repeaters (allergies, notes,
     * pickups): rows with an id update in place, new rows are created,
     * omitted rows are deleted.
     *
     * Takes the relation name, not a relation instance: find() leaves its
     * `where id = ?` on the relation's query, so a shared instance would
     * narrow every later call — the second lookup finds nothing and
     * duplicates its row, and the cleanup delete below matches nothing.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  string  $requiredField  rows with this field blank are skipped
     * @param  \Closure(array<string, mixed>): array<string, mixed>  $mapper
     */
    private function syncChildRows(string $relation, array $rows, string $requiredField, \Closure $mapper): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            if (blank($row[$requiredField] ?? null)) {
                continue;
            }

            $data = $mapper($row);
            $existing = isset($row['id']) ? $this->{$relation}()->find($row['id']) : null;

            if ($existing) {
                $existing->update($data);
                $keptIds[] = $existing->id;
            } else {
                $keptIds[] = $this->{$relation}()->create($data)->id;
            }
        }

        $this->{$relation}()->whereNotIn('id', $keptIds)->delete();
    }
}
