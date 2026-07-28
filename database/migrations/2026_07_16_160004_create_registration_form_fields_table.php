<?php

use App\Enums\RegistrationFormType;
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
        Schema::create('registration_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained();
            $table->enum('form_type', RegistrationFormType::values());
            $table->string('group', 60);
            $table->string('label', 120);
            $table->string('input_type', 30)->default('short_answer');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_custom')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_form_fields');
    }
};
