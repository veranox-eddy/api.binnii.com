<?php

namespace App\Models;

use Database\Factories\CenterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable([
    'organization_id', 'name', 'external_ref', 'email', 'phone', 'phone_country_code',
    'timezone', 'tax_id', 'address_line1', 'address_line2', 'city', 'state', 'country',
    'zip', 'licensed_capacity', 'desired_capacity', 'is_active',
])]
class Center extends Model
{
    /** @use HasFactory<CenterFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(CenterSetting::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function ageRanges(): HasMany
    {
        return $this->hasMany(AgeRange::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function virtualAreas(): HasMany
    {
        return $this->hasMany(VirtualArea::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function registrationFormFields(): HasMany
    {
        return $this->hasMany(RegistrationFormField::class);
    }

    public function subsidyPrograms(): HasMany
    {
        return $this->hasMany(SubsidyProgram::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function messageTemplates(): HasMany
    {
        return $this->hasMany(MessageTemplate::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function menusCalendars(): HasMany
    {
        return $this->hasMany(MenusCalendar::class);
    }

    public function healthScreening(): HasOne
    {
        return $this->hasOne(HealthScreening::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /**
     * The current time in this center's timezone (datetimes are stored UTC
     * and rendered in centers.timezone — see CLAUDE.md).
     */
    public function now(): Carbon
    {
        return now($this->timezone ?: config('app.timezone'));
    }

    /**
     * Prefix used to suggest classroom login usernames. Prefers the prefix
     * the center's existing usernames already use (e.g. "bkmcci" from
     * "bkmcci_infantroom"); falls back to lowercased center-name initials
     * (e.g. "Childcare Centre Inc." → "cci").
     */
    public function loginPrefix(): string
    {
        $existing = $this->classrooms()
            ->whereNotNull('login_username')
            ->pluck('login_username')
            ->map(fn (string $username) => str_contains($username, '_') ? strtok($username, '_') : null)
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        return $existing ?? str($this->name)
            ->lower()
            ->explode(' ')
            ->map(fn (string $word) => mb_substr($word, 0, 1))
            ->implode('');
    }
}
