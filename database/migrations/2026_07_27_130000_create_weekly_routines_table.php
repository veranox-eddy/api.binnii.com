<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A classroom's weekly routine rows = the "experiences" on the wireframe
 * ca-weekly-planner.html (Circle Time / Choice Time / …). They persist across
 * weeks; planned activities hang off a (routine row × date) cell.
 * Schema doc §L, v1.10.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_routines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('color', 9)->nullable(); // hex #RRGGBB; None → null → default grey edge
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_routines');
    }
};
