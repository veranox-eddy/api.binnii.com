<?php

namespace App\Policies;

use App\Models\Child;
use App\Models\Guardian;
use Illuminate\Auth\Access\Response;

/**
 * Guardians reach children only through `child_guardian`. Denials are 404,
 * not 403: a parent guessing ids must not be able to learn that a given
 * child exists at all.
 */
class ChildPolicy
{
    public function view(Guardian $guardian, Child $child): Response
    {
        return $this->linked($guardian, $child);
    }

    public function update(Guardian $guardian, Child $child): Response
    {
        return $this->linked($guardian, $child);
    }

    /**
     * Identity fields — name, birthday, gender, photo — belong to the
     * center's record of the child, so only a guardian the center marked as
     * account admin may rewrite them. Everyone else can still edit their own
     * pivot (relationship, nickname); see UpdateChildProfileRequest.
     */
    public function updateIdentity(Guardian $guardian, Child $child): Response
    {
        if (! $guardian->ownsChild($child->getKey())) {
            return Response::denyAsNotFound();
        }

        return (bool) $guardian->accessTo($child->getKey())['is_account_admin']
            ? Response::allow()
            : Response::deny('Only an account admin can change this child\'s details.');
    }

    private function linked(Guardian $guardian, Child $child): Response
    {
        return $guardian->ownsChild($child->getKey())
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
