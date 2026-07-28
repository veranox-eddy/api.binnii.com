<?php

use App\Enums\ClassroomAlertType;
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
        Schema::create('classroom_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ClassroomAlertType::values());
            $table->time('remind_at');
            $table->date('alert_date')->nullable(); // null = every day (schema doc §H)
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->text('instructions')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classroom_alerts');
    }
};
