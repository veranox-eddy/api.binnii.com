<?php

use App\Enums\ChildGuardianType;
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
        Schema::create('child_guardian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children');
            $table->foreignId('guardian_id')->constrained();
            $table->enum('type', ChildGuardianType::values());
            $table->string('relationship', 60)->nullable();
            $table->boolean('is_emergency')->default(false);
            $table->tinyInteger('priority')->nullable();
            $table->unique(['child_id', 'guardian_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_guardian');
    }
};
