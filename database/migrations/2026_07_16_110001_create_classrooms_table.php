<?php

use App\Enums\DevelopmentalFramework;
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
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained();
            $table->string('name', 120);
            $table->string('display_name', 120)->nullable();
            $table->string('external_ref', 30)->nullable();
            $table->foreignId('age_range_id')->nullable()->constrained();
            $table->integer('desired_capacity')->nullable();
            $table->string('student_staff_ratio', 10)->nullable();
            $table->enum('developmental_framework', DevelopmentalFramework::values())->nullable();
            $table->string('login_username', 100)->nullable()->unique();
            $table->boolean('is_floating')->default(false);
            $table->boolean('photo_sharing_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
