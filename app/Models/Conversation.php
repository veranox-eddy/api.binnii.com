<?php

namespace App\Models;

use App\Enums\ConversationType;
use App\Enums\MessageChannel;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['center_id', 'subject', 'type', 'channel', 'created_by', 'shared_with_teachers', 'archived_at', 'scheduled_at'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    protected $attributes = [
        'channel' => MessageChannel::Email->value,
    ];

    protected function casts(): array
    {
        return [
            'type' => ConversationType::class,
            'channel' => MessageChannel::class,
            'shared_with_teachers' => 'boolean',
            'archived_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    /**
     * Threads this guardian belongs to — the parent API's whole world
     * (API_09).
     *
     * @param  Builder<Conversation>  $query
     */
    public function scopeForGuardian(Builder $query, Guardian $guardian): void
    {
        $query->whereHas('participants', fn ($q) => $q
            ->where('participant_type', 'guardian')
            ->where('participant_id', $guardian->getKey()));
    }

    public function hasGuardianParticipant(Guardian $guardian): bool
    {
        return $this->participants()
            ->where('participant_type', 'guardian')
            ->where('participant_id', $guardian->getKey())
            ->exists();
    }

    /** Display names of guardian participants (the "To" line). */
    public function participantNames(): string
    {
        return $this->participants
            ->filter(fn (ConversationParticipant $p) => $p->role !== 'sender')
            ->map(fn (ConversationParticipant $p) => match (true) {
                $p->participant === null => null,
                method_exists($p->participant, 'fullName') => $p->participant->fullName(),
                default => $p->participant->name,
            })
            ->filter()
            ->unique()
            ->implode(', ');
    }

    /** Audience slugs behind the inbox filter and row tag. */
    public const array AUDIENCE_LABELS = [
        'parent' => 'Parent',
        'staff' => 'Staff',
        'newsletter' => 'Newsletter',
    ];

    /**
     * The row tag on db-messages-inbox.html. Not the same axis as `type`:
     * a thread with staff recipients is Staff whatever its type, and the
     * rest split on message (Parent) vs notice (Newsletter). Compose never
     * mixes guardian and staff recipients, so the order is unambiguous.
     */
    public function audienceTag(): string
    {
        $hasStaffRecipient = $this->participants->contains(
            fn (ConversationParticipant $p) => $p->role === 'recipient' && $p->participant_type === 'staff'
        );

        return match (true) {
            $hasStaffRecipient => self::AUDIENCE_LABELS['staff'],
            $this->type === ConversationType::Notice => self::AUDIENCE_LABELS['newsletter'],
            default => self::AUDIENCE_LABELS['parent'],
        };
    }

    /**
     * audienceTag() at the database level, for the filter dropdown.
     *
     * @param  Builder<Conversation>  $query
     * @return Builder<Conversation>
     */
    public function scopeForAudience(Builder $query, string $audience): Builder
    {
        $guardianRecipients = fn ($q) => $q->where('role', 'recipient')->where('participant_type', 'guardian');

        return match ($audience) {
            'staff' => $query->whereHas('participants', fn ($q) => $q
                ->where('role', 'recipient')->where('participant_type', 'staff')),
            'newsletter' => $query->where('type', ConversationType::Notice)
                ->whereHas('participants', $guardianRecipients),
            'parent' => $query->where('type', ConversationType::Message)
                ->whereHas('participants', $guardianRecipients),
            default => $query,
        };
    }

    /**
     * Threads still waiting to go out: scheduled_at is in the future. The
     * Scheduled tab. scheduled_at is stored UTC and so is now(), so the
     * comparison needs no timezone conversion.
     *
     * @param  Builder<Conversation>  $query
     * @return Builder<Conversation>
     */
    public function scopePendingSchedule(Builder $query): Builder
    {
        return $query->whereNotNull('scheduled_at')->where('scheduled_at', '>', now());
    }

    /**
     * Released threads (they read as sent): never scheduled, or the send time
     * has passed. Inbox / Sent / Archived only ever show these.
     *
     * @param  Builder<Conversation>  $query
     * @return Builder<Conversation>
     */
    public function scopeReleased(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNull('scheduled_at')
            ->orWhere('scheduled_at', '<=', now()));
    }

    /**
     * Guardian participant emails for the thread header — the wireframe
     * prints them in brackets after the names. Staff recipients are left out:
     * the wireframe only ever shows this for guardian threads.
     */
    public function participantEmails(): string
    {
        return $this->participants
            ->filter(fn (ConversationParticipant $p) => $p->role !== 'sender' && $p->participant_type === 'guardian')
            ->map(fn (ConversationParticipant $p) => $p->participant?->email)
            ->filter()
            ->unique()
            ->implode(', ');
    }
}
