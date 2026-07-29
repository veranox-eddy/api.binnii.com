<?php

namespace App\Policies;

use App\Models\Child;
use App\Models\Guardian;
use Illuminate\Auth\Access\Response;

/**
 * The Crew is the set of guardians on one child, so the thing being
 * authorized is the child — registered as `crew.view` / `crew.manage` gates
 * in AppServiceProvider rather than a model policy. Reads are for the whole
 * family; writes stay with an account admin, the family member the center
 * holds responsible for the child's record.
 */
class CrewPolicy
{
    public function viewAny(Guardian $guardian, Child $child): Response
    {
        return $guardian->ownsChild($child->getKey())
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function manage(Guardian $guardian, Child $child): Response
    {
        if (! $guardian->ownsChild($child->getKey())) {
            return Response::denyAsNotFound();
        }

        return (bool) $guardian->accessTo($child->getKey())['is_account_admin']
            ? Response::allow()
            : Response::deny('Only an account admin can manage the Crew.');
    }
}
