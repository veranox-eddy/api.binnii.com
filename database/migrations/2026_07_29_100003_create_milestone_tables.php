<?php

use App\Enums\MilestoneAgeGroup;
use App\Enums\MilestoneCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * API_02 M4. `milestone_definitions` holds both the seeded global list
 * (center_id and child_id null) and a family's own "Add Your Own!" entries
 * (child_id set, is_custom true). `child_milestones` records when a given
 * child reached one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestone_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->nullable()->constrained();
            $table->foreignId('child_id')->nullable()->constrained('children');
            $table->enum('age_group', MilestoneAgeGroup::values());
            $table->enum('category', MilestoneCategory::values());
            $table->string('name', 120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_custom')->default(false);
            $table->timestamps();
            $table->index(['age_group', 'category', 'sort_order']);
        });

        Schema::create('child_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children');
            $table->foreignId('milestone_definition_id')->nullable()->constrained();
            $table->string('custom_name', 120)->nullable();
            $table->date('achieved_on')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('recorded_by_guardian_id')->constrained('guardians');
            $table->timestamps();
            $table->unique(['child_id', 'milestone_definition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_milestones');
        Schema::dropIfExists('milestone_definitions');
    }
};
