<?php

use App\Enums\ApplicationContactType;
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
        Schema::create('application_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained();
            $table->enum('type', ApplicationContactType::values());
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('relationship', 60)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address_line1', 190)->nullable();
            $table->string('address_line2', 120)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 80)->nullable();
            $table->string('zip', 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_contacts');
    }
};
