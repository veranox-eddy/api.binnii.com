<?php

use App\Enums\DaysApprovedPeriod;
use App\Enums\DaysApprovedUnit;
use App\Enums\MaxAbsentPeriod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Day-based, not amount-based (schema decision #7 / v1.1 refactor).
        Schema::create('subsidy_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children');
            $table->foreignId('subsidy_program_id')->constrained();
            $table->smallInteger('days_approved')->nullable();
            $table->enum('days_approved_unit', DaysApprovedUnit::values());
            $table->enum('days_approved_period', DaysApprovedPeriod::values());
            $table->smallInteger('max_absent_days')->nullable();
            $table->enum('max_absent_period', MaxAbsentPeriod::values())->nullable();
            $table->boolean('covid_days_count_absent')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('additional_info')->nullable();
            $table->string('status', 40);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subsidy_agreements');
    }
};
