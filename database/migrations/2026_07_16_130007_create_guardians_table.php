<?php

use App\Enums\GuardianRegistrationStatus;
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
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 190)->nullable();
            $table->string('mobile_country_code', 8)->nullable();
            $table->string('mobile_phone', 30)->nullable();
            $table->string('home_phone', 30)->nullable();
            $table->string('work_phone', 30)->nullable();
            $table->enum('registration_status', GuardianRegistrationStatus::values());
            $table->dateTime('invited_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
