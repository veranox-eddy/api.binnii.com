<?php

namespace App\Http\Controllers\Api;

use App\Enums\MilestoneAgeGroup;
use App\Enums\MilestoneCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCustomMilestoneRequest;
use App\Http\Requests\Api\UpdateChildMilestonesRequest;
use App\Models\Child;
use App\Models\ChildMilestone;
use App\Models\Guardian;
use App\Models\MilestoneDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MilestoneController extends Controller
{
    /** One age group's definitions merged with this child's achievements (S15). */
    public function index(Request $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        $request->validate(['age_group' => [Rule::in(MilestoneAgeGroup::values())]]);
        $ageGroup = MilestoneAgeGroup::from($request->input('age_group', MilestoneAgeGroup::Infant->value));

        $definitions = MilestoneDefinition::query()
            ->forChild($child)
            ->where('age_group', $ageGroup)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $achievements = ChildMilestone::where('child_id', $child->getKey())
            ->whereIn('milestone_definition_id', $definitions->pluck('id'))
            ->get()
            ->keyBy('milestone_definition_id');

        $grouped = $definitions->groupBy(fn (MilestoneDefinition $definition) => $definition->category->value);

        return response()->json([
            'age_group' => $ageGroup->value,
            'categories' => collect(MilestoneCategory::values())
                ->mapWithKeys(fn (string $category) => [
                    $category => $grouped->get($category, collect())
                        ->map(fn (MilestoneDefinition $definition) => $this->item($definition, $achievements->get($definition->getKey())))
                        ->values(),
                ]),
        ]);
    }

    /** "Submit Milestones" — the whole form comes back at once. */
    public function upsert(UpdateChildMilestonesRequest $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        $items = collect($request->validated('items'));
        $this->assertDefinitionsBelongTo($child, $items->pluck('milestone_definition_id'));

        DB::transaction(function () use ($items, $child) {
            foreach ($items as $item) {
                ChildMilestone::updateOrCreate([
                    'child_id' => $child->getKey(),
                    'milestone_definition_id' => $item['milestone_definition_id'],
                ], [
                    'achieved_on' => $item['achieved_on'] ?? null,
                    'description' => $item['description'] ?? null,
                    'recorded_by_guardian_id' => $this->guardian()->getKey(),
                ]);
            }
        });

        return response()->json(['saved' => $items->count()]);
    }

    /** "Add Your Own!" — a definition only this child ever sees. */
    public function storeCustom(StoreCustomMilestoneRequest $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        $item = DB::transaction(function () use ($request, $child) {
            $definition = MilestoneDefinition::create([
                'child_id' => $child->getKey(),
                'age_group' => $request->string('age_group')->value(),
                'category' => $request->string('category')->value(),
                'name' => $request->string('name')->value(),
                'sort_order' => MilestoneDefinition::query()
                    ->forChild($child)
                    ->where('age_group', $request->string('age_group')->value())
                    ->where('category', $request->string('category')->value())
                    ->count(),
                'is_custom' => true,
            ]);

            $achievement = ChildMilestone::create([
                'child_id' => $child->getKey(),
                'milestone_definition_id' => $definition->getKey(),
                'achieved_on' => $request->input('achieved_on'),
                'description' => $request->input('description'),
                'recorded_by_guardian_id' => $this->guardian()->getKey(),
            ]);

            return $this->item($definition, $achievement);
        });

        return response()->json(['data' => $item], 201);
    }

    /** @return array<string, mixed> */
    private function item(MilestoneDefinition $definition, ?ChildMilestone $achievement): array
    {
        return [
            'definition_id' => $definition->getKey(),
            'name' => $definition->name,
            'achieved_on' => $achievement?->achieved_on?->toDateString(),
            'description' => $achievement?->description,
            'is_custom' => $definition->is_custom,
        ];
    }

    /**
     * Every submitted definition must be one this child can see — global,
     * their center's, or their own custom items. A sibling's or another
     * family's definition id is rejected wholesale.
     *
     * @param  Collection<int, int>  $ids
     */
    private function assertDefinitionsBelongTo(Child $child, $ids): void
    {
        $visible = MilestoneDefinition::query()
            ->forChild($child)
            ->whereKey($ids->all())
            ->count();

        if ($visible !== $ids->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'One or more milestones do not belong to this child.',
            ]);
        }
    }

    private function guardian(): Guardian
    {
        return auth('guardian')->user();
    }
}
