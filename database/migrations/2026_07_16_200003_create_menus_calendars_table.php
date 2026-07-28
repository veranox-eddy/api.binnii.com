<?php

use App\Enums\MenusCalendarType;
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
        Schema::create('menus_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained();
            $table->enum('type', MenusCalendarType::values());
            $table->string('name', 150);
            $table->boolean('parent_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menus_calendar_id')->constrained();
            $table->date('event_date');
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('menus_calendars');
    }
};
