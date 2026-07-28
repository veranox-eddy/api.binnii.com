<?php

use App\Enums\EntryType;
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
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children');
            $table->foreignId('classroom_id')->constrained();
            $table->foreignId('staff_id')->nullable()->constrained('staff');
            $table->enum('type', EntryType::values());
            $table->dateTime('occurred_at');
            $table->json('payload');
            $table->timestamps();
            $table->index(['child_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
