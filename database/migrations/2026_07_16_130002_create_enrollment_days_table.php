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
        Schema::create('enrollment_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained();
            $table->unsignedTinyInteger('weekday'); // 0 (Sunday) – 6 (Saturday), Carbon convention
            $table->unique(['enrollment_id', 'weekday']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_days');
    }
};
