<?php

use App\Enums\Gender;
use App\Enums\StaffStatus;
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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->enum('gender', Gender::values())->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('position', 80)->nullable();
            $table->foreignId('primary_classroom_id')->nullable()->constrained('classrooms');
            $table->boolean('is_floating')->default(false);
            $table->enum('status', StaffStatus::values());
            $table->date('hired_on')->nullable();
            $table->date('deactivated_on')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
