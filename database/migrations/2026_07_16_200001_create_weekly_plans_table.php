<?php

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
        Schema::create('weekly_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained();
            $table->date('week_start_date');
            $table->timestamps();
        });

        Schema::create('weekly_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_plan_id')->constrained();
            $table->date('plan_date');
            $table->foreignId('activity_id')->nullable()->constrained();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_plan_items');
        Schema::dropIfExists('weekly_plans');
    }
};
