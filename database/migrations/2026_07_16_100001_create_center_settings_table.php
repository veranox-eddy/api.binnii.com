<?php

use App\Enums\AttendanceTimeFormat;
use App\Enums\ChildNameDisplay;
use App\Enums\SignInIdentification;
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
        Schema::create('center_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->unique()->constrained();
            $table->boolean('phone_visible_on_app')->default(false);
            $table->unsignedTinyInteger('delayed_media_hours')->default(0);
            // "Default 18:00" is applied by the seeder/UI, not the column (schema doc v1.2).
            $table->time('auto_send_report_time')->nullable();
            $table->enum('attendance_time_format', AttendanceTimeFormat::values())->default(AttendanceTimeFormat::TwelveHour->value);
            $table->boolean('teacher_editable_timecards')->default(false);
            $table->enum('sign_in_identification', SignInIdentification::values())->default(SignInIdentification::None->value);
            $table->enum('child_name_display', ChildNameDisplay::values())->default(ChildNameDisplay::FullLast->value);
            $table->boolean('parents_can_sign_in')->default(false);
            $table->boolean('safe_pickup')->default(false);
            $table->boolean('checkin_zone_enabled')->default(false);
            $table->boolean('sign_in_code_enabled')->default(false);
            $table->boolean('classroom_access')->default(false);
            $table->boolean('parents_can_mark_absent')->default(false);
            $table->boolean('is_full_week_center')->default(false);
            $table->boolean('curriculum_enabled')->default(false);
            $table->date('curriculum_trial_ends_on')->nullable();
            $table->boolean('staff_management_enabled')->default(false);
            $table->boolean('smart_billing_enabled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('center_settings');
    }
};
