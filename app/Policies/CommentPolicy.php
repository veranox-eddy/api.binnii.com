<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Guardian;
use Illuminate\Auth\Access\Response;

/**
 * A comment is as private as the thing it hangs off: whoever may see the
 * photo or journal entry may see (and like) its thread. Deleting stays with
 * the author — a guardian never removes the center's replies or another
 * family member's words.
 */
class CommentPolicy
{
    public function view(Guardian $guardian, Comment $comment): Response
    {
        return $this->targetVisible($guardian, $comment)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(Guardian $guardian, Comment $comment): Response
    {
        if (! $this->targetVisible($guardian, $comment)) {
            return Response::denyAsNotFound();
        }

        return $comment->guardian_id === $guardian->getKey()
            ? Response::allow()
            : Response::deny('Only the guardian who wrote this comment can delete it.');
    }

    private function targetVisible(Guardian $guardian, Comment $comment): bool
    {
        if ($comment->journal_entry_id !== null) {
            return $comment->journalEntry->isVisibleTo($guardian);
        }

        if ($comment->media_id !== null) {
            return (new MediaPolicy)->view($guardian, $comment->media)->allowed();
        }

        return false;
    }
}
