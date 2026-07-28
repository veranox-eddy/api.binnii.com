<?php

use App\Enums\ApplicationStage;
use App\Enums\ApplicationStatus;
use App\Enums\ChildGender;
use App\Enums\PreferredTimeOfDay;
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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained();
            $table->foreignId('child_id')->nullable()->constrained('children');
            $table->string('child_first_name', 80);
            $table->string('child_last_name', 80);
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ChildGender::values())->nullable();
            $table->string('address_line1', 190)->nullable();
            $table->string('address_line2', 120)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 80)->nullable();
            $table->string('zip', 20)->nullable();
            $table->foreignId('classroom_id')->nullable()->constrained();
            $table->date('preferred_start_date')->nullable();
            $table->enum('preferred_time_of_day', PreferredTimeOfDay::values())->nullable();
            $table->json('preferred_weekly_days')->nullable();
            $table->boolean('subsidy_flag')->default(false);
            $table->integer('priority')->nullable();
            $table->text('internal_notes')->nullable();
            $table->enum('stage', ApplicationStage::values());
            $table->enum('status', ApplicationStatus::values());
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('invite_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
