<?php

use App\Enums\ScreeningAdministeredBy;
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
        Schema::create('health_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->unique()->constrained();
            $table->boolean('staff_administered_enabled')->default(false);
            $table->boolean('family_administered_enabled')->default(false);
            $table->json('questions');
            $table->timestamps();
        });

        // Results are written by the classroom dashboard / parent app
        // (out of scope here) — model only, no screen.
        Schema::create('health_screening_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children');
            $table->date('screened_on');
            $table->enum('administered_by', ScreeningAdministeredBy::values());
            $table->boolean('passed');
            $table->json('answers')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_screening_results');
        Schema::dropIfExists('health_screenings');
    }
};
