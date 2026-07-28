<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\StaffStatus;
use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'center_id', 'user_id', 'first_name', 'last_name', 'date_of_birth', 'gender',
    'email', 'phone', 'avatar_path', 'position', 'primary_classroom_id',
    'is_floating', 'status', 'hired_on', 'deactivated_on',
    'address_line1', 'address_line2', 'city', 'state', 'country', 'zip',
])]
class Staff extends Model
{
    /** @use HasFactory<StaffFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Weekday numbers (Carbon convention, 0 = Sunday) to daychip labels.
     */
    public const array WEEKDAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];

    /**
     * The profile form does not post a status — it is derived from the
     * enrollments after the row exists, so the NOT NULL column needs a seed.
     */
    protected $attributes = [
        'status' => StaffStatus::Active->value,
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'gender' => Gender::class,
            'is_floating' => 'boolean',
            'status' => StaffStatus::class,
            'hired_on' => 'date',
            'deactivated_on' => 'date',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function primaryClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'primary_classroom_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StaffEnrollment::class);
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(StaffCertification::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(StaffRecord::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(StaffNote::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(StaffEmergencyContact::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(StaffAbsence::class);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * The weekday numbers this staff member works, pooled across every
     * enrollment (the profile form carries the schedule per classroom).
     *
     * @return array<int, int>
     */
    public function scheduledWeekdays(): array
    {
        return $this->enrollments
            ->flatMap(fn (StaffEnrollment $enrollment) => $enrollment->days->pluck('weekday'))
            ->unique()
            ->all();
    }

    /**
     * Daychip labels for the days this staff member is scheduled, in Mon–Sun
     * display order.
     *
     * @return Collection<int, string>
     */
    public function scheduledDayNames(): Collection
    {
        return collect(self::WEEKDAYS)->only($this->scheduledWeekdays())->values();
    }

    /**
     * The classrooms this staff member is enrolled in, by name. Requires
     * enrollments.classroom to be loaded.
     *
     * @return Collection<int, string>
     */
    public function classroomNames(): Collection
    {
        return $this->enrollments
            ->map(fn (StaffEnrollment $enrollment) => $enrollment->classroom->name)
            ->unique()
            ->values();
    }

    /**
     * Replace the classroom enrollments (and their weekly days) with the
     * submitted rows; id-keyed so history (timestamps) survives edits.
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
                'start_date' => $row['start_date'] ?? null,
                'end_date' => $row['end_date'] ?? null,
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
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function syncEmergencyContacts(array $rows): void
    {
        $this->syncStaffRows('emergencyContacts', $rows, 'name', fn (array $row) => [
            'name' => $row['name'],
            'relationship' => $row['relationship'] ?? null,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function syncNotes(array $rows): void
    {
        $this->syncStaffRows('notes', $rows, 'body', fn (array $row) => [
            'category' => $row['category'],
            'body' => $row['body'],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows  file_path is filled in by the controller after storing the upload
     */
    public function syncRecords(array $rows): void
    {
        $this->syncDocumentRows('records', $rows);
    }

    /**
     * Sync certifications with the submitted rows. Rows carrying an id are
     * updated in place (preserving history); rows without one are created;
     * rows omitted from the payload are deleted.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function syncCertifications(array $rows): void
    {
        $this->syncDocumentRows('certifications', $rows);
    }

    /**
     * Shared upsert for the two document repeaters (records, certifications):
     * same columns, and both manage an uploaded file alongside the row —
     * replaced uploads and removed rows delete their file from the public disk.
     *
     * Takes the relation name, not a relation instance: find() leaves its
     * `where id = ?` on the relation's query, which would then narrow the
     * cleanup delete below to nothing.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncDocumentRows(string $relation, array $rows): void
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

            $existing = isset($row['id']) ? $this->{$relation}()->find($row['id']) : null;

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
                $keptIds[] = $this->{$relation}()->create($data)->id;
            }
        }

        foreach ($this->{$relation}()->whereNotIn('id', $keptIds)->get() as $stale) {
            if ($stale->file_path) {
                Storage::disk('public')->delete($stale->file_path);
            }
            $stale->delete();
        }
    }

    /**
     * Shared id-keyed upsert for the simple repeaters (emergency contacts,
     * notes): rows with an id update in place, new rows are created, omitted
     * rows are deleted. Relation name, not instance — see syncDocumentRows().
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  string  $requiredField  rows with this field blank are skipped
     * @param  \Closure(array<string, mixed>): array<string, mixed>  $mapper
     */
    private function syncStaffRows(string $relation, array $rows, string $requiredField, \Closure $mapper): void
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

    public function deactivate(): void
    {
        $this->update([
            'status' => StaffStatus::Deactivated,
            'deactivated_on' => now()->toDateString(),
        ]);
    }
}
