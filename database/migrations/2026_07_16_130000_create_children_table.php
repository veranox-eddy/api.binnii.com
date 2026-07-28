<?php

use App\Enums\ChildGender;
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
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->date('date_of_birth');
            $table->enum('gender', ChildGender::values());
            $table->string('photo_path')->nullable();
            $table->boolean('photo_consent')->default(false);
            $table->boolean('is_subsidized')->default(false);
            $table->string('address_line1', 190)->nullable();
            $table->string('address_line2', 120)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 80)->nullable();
            $table->string('zip', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
