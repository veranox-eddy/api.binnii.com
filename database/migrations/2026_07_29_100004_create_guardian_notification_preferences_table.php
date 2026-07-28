<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * API_02 M5. Parent-side email toggles. Distinct from the center-side
 * `notification_preferences` table (which is per staff user / channel) —
 * do not conflate the two.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_id')->constrained('guardians')->cascadeOnDelete();
            $table->boolean('report_started')->default(true);
            $table->boolean('report_ready')->default(true);
            $table->boolean('new_entry')->default(true);
            $table->boolean('new_photo')->default(true);
            $table->boolean('new_comment')->default(true);
            $table->boolean('classroom_photos')->default(true);
            $table->timestamps();
            $table->unique('guardian_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_notification_preferences');
    }
};
