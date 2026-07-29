<?php

namespace App\Policies;

use App\Enums\MediaStatus;
use App\Models\Guardian;
use App\Models\Media;
use Illuminate\Auth\Access\Response;

/**
 * Center media is read-only to guardians. A photo is theirs to see when it
 * is tagged to one of their children and the center has released it.
 */
class MediaPolicy
{
    /**
     * Commenting and liking follow seeing: whoever may download a photo may
     * also react to it (API_06).
     */
    public function view(Guardian $guardian, Media $media): Response
    {
        return $this->download($guardian, $media);
    }

    public function download(Guardian $guardian, Media $media): Response
    {
        $childIds = $media->children()->pluck('children.id');
        $mine = $childIds->filter(fn (int $id) => $guardian->ownsChild($id));

        if ($mine->isEmpty()) {
            return Response::denyAsNotFound();
        }

        if ($media->status !== MediaStatus::Sent) {
            // Not yet released: the parent is not supposed to know it exists.
            return Response::denyAsNotFound();
        }

        // A group photo shows other people's children, so it needs the
        // full-photo-access flag the center sets per guardian (API_07).
        // A photo of their child alone never does.
        if ($childIds->count() > $mine->count()
            && ! $mine->contains(fn (int $id) => (bool) $guardian->accessTo($id)['has_full_photo_access'])) {
            return Response::deny('This photo includes other children, and your account does not have full photo access.');
        }

        return Response::allow();
    }
}
