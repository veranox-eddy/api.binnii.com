<?php

use App\Enums\EnrollmentStatus;
use App\Enums\Rotation;
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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children');
            $table->foreignId('classroom_id')->constrained();
            $table->enum('status', EnrollmentStatus::values());
            $table->enum('rotation', Rotation::values())->nullable();
            $table->date('enrolled_on')->nullable();
            $table->date('graduated_on')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
