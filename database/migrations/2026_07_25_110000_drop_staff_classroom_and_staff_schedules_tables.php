<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * staff_enrollments / staff_enrollment_days took over both jobs when the
     * Add Teacher Profile form was rebuilt: nothing reads or writes these two
     * tables any more (schema doc section E, v1.4).
     */
    public function up(): void
    {
        Schema::dropIfExists('staff_schedules');
        Schema::dropIfExists('staff_classroom');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('staff_classroom', function (Blueprint $table) {
            $table->foreignId('staff_id')->constrained('staff');
            $table->foreignId('classroom_id')->constrained();
            $table->primary(['staff_id', 'classroom_id']);
        });

        Schema::create('staff_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff');
            $table->foreignId('classroom_id')->nullable()->constrained();
            $table->unsignedTinyInteger('weekday'); // 0 (Sunday) – 6 (Saturday), Carbon convention
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
        });
    }
};
