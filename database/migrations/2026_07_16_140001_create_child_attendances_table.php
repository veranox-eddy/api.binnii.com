<?php

use App\Enums\AttendanceStatus;
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
        Schema::create('child_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children');
            $table->foreignId('classroom_id')->constrained();
            $table->date('attendance_date');
            $table->dateTime('check_in_at')->nullable();
            $table->string('check_in_by', 120)->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->string('check_out_by', 120)->nullable();
            $table->string('check_in_signature')->nullable();
            $table->string('check_out_signature')->nullable();
            $table->enum('status', AttendanceStatus::values());
            $table->foreignId('moved_to_classroom_id')->nullable()->constrained('classrooms');
            $table->foreignId('moved_to_virtual_area_id')->nullable()->constrained('virtual_areas');
            $table->timestamps();
            $table->index(['classroom_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_attendances');
    }
};
