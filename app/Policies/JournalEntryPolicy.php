<?php

namespace App\Policies;

use App\Models\Guardian;
use App\Models\JournalEntry;
use Illuminate\Auth\Access\Response;

/**
 * Journal entries belong to the guardian who wrote them. Anyone outside the
 * child's family gets a 404 — that a given entry exists is itself private.
 */
class JournalEntryPolicy
{
    public function view(Guardian $guardian, JournalEntry $entry): Response
    {
        return $entry->isVisibleTo($guardian) ? Response::allow() : Response::denyAsNotFound();
    }

    public function update(Guardian $guardian, JournalEntry $entry): Response
    {
        return $this->authorOnly($guardian, $entry);
    }

    public function delete(Guardian $guardian, JournalEntry $entry): Response
    {
        return $this->authorOnly($guardian, $entry);
    }

    /**
     * Sharing with the Crew is the author's call, but an account admin can
     * also step in — they are the family member the center holds
     * responsible for the child's record.
     */
    public function share(Guardian $guardian, JournalEntry $entry): Response
    {
        if (! $guardian->ownsChild($entry->child_id)) {
            return Response::denyAsNotFound();
        }

        $isAuthor = $entry->guardian_id === $guardian->getKey();
        $isAdmin = (bool) $guardian->accessTo($entry->child_id)['is_account_admin'];

        return $isAuthor || $isAdmin
            ? Response::allow()
            : Response::deny('Only the author or an account admin can change who sees this entry.');
    }

    private function authorOnly(Guardian $guardian, JournalEntry $entry): Response
    {
        if (! $entry->isVisibleTo($guardian)) {
            return Response::denyAsNotFound();
        }

        return $entry->guardian_id === $guardian->getKey()
            ? Response::allow()
            : Response::deny('Only the guardian who wrote this entry can change it.');
    }
}
