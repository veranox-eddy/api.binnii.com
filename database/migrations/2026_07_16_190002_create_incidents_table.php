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
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children');
            $table->foreignId('classroom_id')->nullable()->constrained();
            $table->string('type_of_incident', 120);
            $table->dateTime('occurred_at');
            $table->text('description')->nullable();
            $table->boolean('parent_notified')->default(false);
            $table->dateTime('parent_notified_at')->nullable();
            $table->string('parent_signature', 190)->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('staff');
            $table->timestamps();
        });

        Schema::create('incident_staff', function (Blueprint $table) {
            $table->foreignId('incident_id')->constrained();
            $table->foreignId('staff_id')->constrained('staff');
            $table->primary(['incident_id', 'staff_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_staff');
        Schema::dropIfExists('incidents');
    }
};
