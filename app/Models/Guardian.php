<?php

namespace App\Models;

use App\Enums\GuardianRegistrationStatus;
use App\Notifications\GuardianResetPassword;
use Database\Factories\GuardianFactory;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

/*
 * `password` and the notification/email preference columns are added by the
 * parent API (API_02 M1) and are set through dedicated flows — activation,
 * reset, settings — so they stay out of the admin's fillable list.
 */
#[Fillable([
    'center_id', 'first_name', 'last_name', 'email', 'mobile_country_code',
    'mobile_phone', 'home_phone', 'work_phone', 'registration_status', 'invited_at',
    'receive_fewer_emails', 'email_language',
])]
#[Hidden(['password', 'remember_token'])]
class Guardian extends Model implements AuthenticatableContract, CanResetPasswordContract, JWTSubject
{
    /** @use HasFactory<GuardianFactory> */
    use AuthenticatableTrait, CanResetPassword, HasFactory, Notifiable;

    /**
     * Relationship options offered by db-add-child.html's emergency contact
     * and designated pickup selects (its shared RELOPTS list). NOTE:
     * me-editprofile.html — a parent-facing "Edit Profile" screen, not part
     * of this admin console — offers a different, shorter list; if that page
     * is ever built, give it its own constant the way
     * StaffEmergencyContact::RELATIONSHIPS does for the staff form, instead
     * of reusing this one. That split is why
     * child_guardian.relationship stays a varchar — see schema doc §D.
     */
    public const array RELATIONSHIPS = [
        'Spouse', 'Parent/Guardian', 'Grandparent', 'Sibling', 'Aunt/Uncle',
        'Family member', 'Friend', 'Nanny', 'Primary healthcare practitioner', 'Other',
    ];

    protected $attributes = [
        'registration_status' => GuardianRegistrationStatus::NotInvited->value,
    ];

    protected function casts(): array
    {
        return [
            'registration_status' => GuardianRegistrationStatus::class,
            'invited_at' => 'datetime',
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'receive_fewer_emails' => 'boolean',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'child_guardian')
            ->withPivot([
                'type', 'relationship', 'is_emergency', 'priority',
                'is_account_admin', 'has_full_photo_access', 'nickname',
            ]);
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(GuardianNotificationPreference::class);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Guardians who could still receive an invite (banner count / resend-all).
     *
     * @param  Builder<Guardian>  $query
     */
    public function scopeUnregistered($query): void
    {
        $query->where('registration_status', '!=', GuardianRegistrationStatus::Registered)
            ->whereNotNull('email');
    }

    public function markInvited(): void
    {
        $this->update([
            'registration_status' => GuardianRegistrationStatus::Invited,
            'invited_at' => now(),
        ]);
    }

    public function markRegistered(): void
    {
        $this->update(['registration_status' => GuardianRegistrationStatus::Registered]);
    }

    /**
     * Only a guardian who finished activation holds a usable login. An
     * invited-but-not-activated row has no password, and `attempt()` on an
     * empty hash must never be the thing standing between the two.
     */
    public function canLogIn(): bool
    {
        return $this->registration_status === GuardianRegistrationStatus::Registered
            && filled($this->password);
    }

    /**
     * Every parent-API query narrows through this: a guardian may only touch
     * children linked to them on `child_guardian`.
     */
    public function ownsChild(int $childId): bool
    {
        return $this->children()->whereKey($childId)->exists();
    }

    /** @return array<string, mixed>|null the `child_guardian` row for this child */
    public function accessTo(int $childId): ?array
    {
        $child = $this->children()->whereKey($childId)->first();

        return $child?->pivot->only(['is_account_admin', 'has_full_photo_access', 'nickname', 'relationship', 'type']);
    }

    /** The reset link points at the SPA, not at a route in this API. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new GuardianResetPassword($token));
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * `center_id` rides along so middleware can reject a token minted before
     * the guardian moved centers without a second query; `type` keeps a
     * guardian token from ever being mistaken for a staff one.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return ['center_id' => $this->center_id, 'type' => 'guardian'];
    }

    /**
     * The wireframe uses a single "Name" input; the last word becomes the
     * last name ("Lucy Lee Peng" → ["Lucy Lee", "Peng"], "Osman" → ["Osman", ""]).
     *
     * @return array{0: string, 1: string}
     */
    public static function splitName(string $name): array
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];

        return count($words) > 1
            ? [implode(' ', array_slice($words, 0, -1)), end($words)]
            : [$words[0] ?? '', ''];
    }
}
