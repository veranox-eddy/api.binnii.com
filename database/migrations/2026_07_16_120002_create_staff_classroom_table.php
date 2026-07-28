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
        Schema::create('staff_classroom', function (Blueprint $table) {
            $table->foreignId('staff_id')->constrained('staff');
            $table->foreignId('classroom_id')->constrained();
            $table->primary(['staff_id', 'classroom_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_classroom');
    }
};
