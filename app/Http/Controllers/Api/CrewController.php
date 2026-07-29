<?php

namespace App\Http\Controllers\Api;

use App\Enums\ChildGuardianType;
use App\Enums\GuardianRegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCrewMemberRequest;
use App\Http\Requests\Api\UpdateCrewMemberRequest;
use App\Http\Resources\CrewMemberResource;
use App\Models\Child;
use App\Models\Guardian;
use App\Notifications\GuardianInvite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class CrewController extends Controller
{
    public function index(Child $child): AnonymousResourceCollection
    {
        $this->authorize('crew.view', $child);

        return CrewMemberResource::collection(
            $child->guardians()->orderBy('guardians.first_name')->get(),
        );
    }

    public function store(StoreCrewMemberRequest $request, Child $child): JsonResponse
    {
        $this->authorize('crew.manage', $child);

        $members = collect($request->validated('members'))
            ->map(fn (array $row) => $this->addMember($child, $row));

        return CrewMemberResource::collection($members)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Child $child, Guardian $guardian): CrewMemberResource
    {
        $this->authorize('crew.view', $child);

        return new CrewMemberResource($this->member($child, $guardian));
    }

    public function update(UpdateCrewMemberRequest $request, Child $child, Guardian $guardian): CrewMemberResource
    {
        $this->authorize('crew.manage', $child);
        $this->member($child, $guardian);

        $pivot = $request->only(['relationship', 'is_account_admin', 'has_full_photo_access', 'nickname']);

        if (array_key_exists('relationship', $pivot)) {
            $pivot['type'] = $this->typeFor($pivot['relationship'])->value;
        }

        if ($pivot !== []) {
            $child->guardians()->updateExistingPivot($guardian->getKey(), $pivot);
        }

        if ($request->has('email')) {
            $guardian->update(['email' => $request->string('email')->value()]);
        }

        return new CrewMemberResource($this->member($child, $guardian));
    }

    public function destroy(Child $child, Guardian $guardian): Response
    {
        $this->authorize('crew.manage', $child);
        $member = $this->member($child, $guardian);

        // Someone must stay responsible for the child's record.
        if ((bool) $member->pivot->is_account_admin && ! $this->hasAnotherAdmin($child, $guardian)) {
            throw ValidationException::withMessages([
                'guardian' => 'This is the only account admin for this child — make someone else an admin first.',
            ]);
        }

        // The pivot only: the guardian may still belong to other children.
        $child->guardians()->detach($guardian->getKey());

        return response()->noContent();
    }

    /**
     * Find-or-create by email within the child's center, link with the
     * chosen flags, and invite anyone who has never activated.
     *
     * @param  array<string, mixed>  $row
     */
    private function addMember(Child $child, array $row): Guardian
    {
        $guardian = Guardian::firstOrNew([
            'center_id' => $child->center_id,
            'email' => $row['email'],
        ]);

        if (! $guardian->exists) {
            [$firstName, $lastName] = Guardian::splitName($row['name']);
            $guardian->fill(['first_name' => $firstName, 'last_name' => $lastName])->save();
        }

        $child->guardians()->syncWithoutDetaching([
            $guardian->getKey() => [
                'type' => $this->typeFor($row['relationship'])->value,
                'relationship' => $row['relationship'],
                'is_account_admin' => (bool) ($row['is_account_admin'] ?? false),
                'has_full_photo_access' => true,
            ],
        ]);

        if ($guardian->registration_status !== GuardianRegistrationStatus::Registered) {
            $guardian->markInvited();
            $guardian->notify(new GuardianInvite);
        }

        return $this->member($child, $guardian);
    }

    /** The `{guardian}` binding is only a Crew member if the pivot row exists. */
    private function member(Child $child, Guardian $guardian): Guardian
    {
        $member = $child->guardians()->whereKey($guardian->getKey())->first();

        abort_if($member === null, Response::HTTP_NOT_FOUND);

        return $member;
    }

    private function hasAnotherAdmin(Child $child, Guardian $leaving): bool
    {
        return $child->guardians()
            ->whereKeyNot($leaving->getKey())
            ->wherePivot('is_account_admin', true)
            ->exists();
    }

    /** The S12 form only distinguishes parents from everyone else. */
    private function typeFor(string $relationship): ChildGuardianType
    {
        return in_array($relationship, ['Parent', 'Guardian'], strict: true)
            ? ChildGuardianType::Parent
            : ChildGuardianType::Guardian;
    }
}
