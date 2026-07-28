<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateChildProfileRequest;
use App\Http\Resources\ChildResource;
use App\Http\Resources\ChildSummaryResource;
use App\Models\Child;
use App\Models\Guardian;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class ChildController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ChildSummaryResource::collection($this->guardian()->children()
            ->with(['center.settings', 'enrollments.classroom'])
            ->orderBy('children.first_name')
            ->get());
    }

    public function show(Child $child): ChildResource
    {
        $this->authorize('view', $child);

        return new ChildResource($this->scoped($child->getKey()));
    }

    /**
     * Identity fields land on `children`; relationship and nickname land on
     * this guardian's pivot row only — a co-parent's row is never touched.
     */
    public function update(UpdateChildProfileRequest $request, Child $child): ChildResource
    {
        $this->authorize('update', $child);

        $guardian = $this->guardian();

        if ($this->changesIdentity($request, $child)) {
            $this->authorize('updateIdentity', $child);

            $child->fill([
                'first_name' => $request->string('first_name')->value(),
                'last_name' => $request->string('last_name')->value(),
                'date_of_birth' => $request->string('birthday')->value(),
                'gender' => $request->string('gender')->value(),
            ]);

            if ($request->hasFile('photo')) {
                $previous = $child->photo_path;
                $child->photo_path = $request->file('photo')->store('children', 'public');

                if ($previous) {
                    Storage::disk('public')->delete($previous);
                }
            }

            $child->save();
        }

        // updateExistingPivot, not sync/attach: sync would rewrite the whole
        // relation and drop the other guardians' rows.
        $guardian->children()->updateExistingPivot($child->getKey(), [
            'relationship' => $request->input('relationship'),
            'nickname' => $request->input('nickname'),
        ]);

        return new ChildResource($this->scoped($child->getKey()));
    }

    /**
     * Non-admin guardians may still submit the whole form — the SPA sends
     * every field — so only an actual change to an identity field trips the
     * `updateIdentity` check.
     */
    private function changesIdentity(UpdateChildProfileRequest $request, Child $child): bool
    {
        return $request->hasFile('photo')
            || $request->string('first_name')->value() !== $child->first_name
            || $request->string('last_name')->value() !== $child->last_name
            || $request->string('birthday')->value() !== $child->date_of_birth?->toDateString()
            || $request->string('gender')->value() !== $child->gender?->value;
    }

    /** Re-read through the relation so `pivot` and `access` are populated. */
    private function scoped(int $childId): Child
    {
        return $this->guardian()->children()
            ->with(['center.settings', 'enrollments.classroom'])
            ->findOrFail($childId);
    }

    private function guardian(): Guardian
    {
        return auth('guardian')->user();
    }
}
