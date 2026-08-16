<?php

namespace App\Models;

use App\Enums\AccessLevel;
use App\Enums\UserType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/*
 * Privilege fields (type, access_level, is_active) are intentionally NOT
 * fillable — assign them explicitly (forceFill in seeders, dedicated code in
 * the future user-management slice) so request payloads can never escalate.
 */
#[Fillable([
    'organization_id', 'name', 'email', 'username', 'password', 'phone',
    'avatar_path', 'last_active_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /** Memo of which center id currentCenter() resolved to, and from what. */
    private bool $currentCenterResolved = false;

    private ?int $currentCenterFor = null;

    private ?int $currentCenterId = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'type' => UserType::class,
            'access_level' => AccessLevel::class,
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** The HR record linked to this login, when one exists (staff.user_id). */
    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Centers assigned to a center-level user. Organization-level users can
     * access every center in their organization and have no pivot rows.
     */
    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class)->withTimestamps();
    }

    /**
     * Query for every center this user may access, per users.access_level.
     *
     * @return HasMany<Center, Organization>|BelongsToMany<Center, $this>
     */
    public function accessibleCenters(): HasMany|BelongsToMany
    {
        return $this->access_level === AccessLevel::Organization
            ? $this->organization->centers()
            : $this->centers();
    }

    public function canAccessCenter(int $centerId): bool
    {
        return $this->accessibleCenters()->whereKey($centerId)->exists();
    }

    /**
     * The center the user is currently working in: the one last chosen via
     * Switch Center (session `current_center_id`) while it is still
     * accessible, otherwise the first accessible center. Null when the user
     * has no center yet — routes needing one sit behind the
     * EnsureUserHasCenter middleware.
     *
     * Which center that is gets memoized (this runs from dozens of call
     * sites per page and the access check is an extra query), but the model
     * itself is fetched per call, exactly as before: callers have always
     * received a fresh instance and some of them re-read a center they just
     * wrote to.
     */
    public function currentCenter(): ?Center
    {
        $chosen = session()->get('current_center_id');
        $chosen = is_numeric($chosen) ? (int) $chosen : null;

        if (! $this->currentCenterResolved || $this->currentCenterFor !== $chosen) {
            $this->currentCenterResolved = true;
            $this->currentCenterFor = $chosen;

            // A stale session value (the center was un-assigned meanwhile)
            // must never grant access — fall back to the default center.
            $this->currentCenterId = $chosen !== null && $this->canAccessCenter($chosen)
                ? $chosen
                : $this->accessibleCenters()->orderBy('centers.id')->value('centers.id');
        }

        return $this->currentCenterId ? Center::find($this->currentCenterId) : null;
    }
}
