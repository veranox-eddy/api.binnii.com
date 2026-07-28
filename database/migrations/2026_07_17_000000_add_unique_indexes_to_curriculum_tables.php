<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One weekly plan per classroom per week and one curriculum assignment
     * per classroom (schema doc v1.3) — the application upserts, the index
     * guards against concurrent duplicates.
     */
    public function up(): void
    {
        Schema::table('weekly_plans', function (Blueprint $table) {
            $table->unique(['classroom_id', 'week_start_date']);
        });

        Schema::table('curriculum_assignments', function (Blueprint $table) {
            $table->unique('classroom_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_plans', function (Blueprint $table) {
            $table->dropUnique(['classroom_id', 'week_start_date']);
        });

        Schema::table('curriculum_assignments', function (Blueprint $table) {
            $table->dropUnique(['classroom_id']);
        });
    }
};
