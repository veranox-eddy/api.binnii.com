<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_plan_items', function (Blueprint $table) {
            // An item belongs to a routine row; removing the row removes its
            // activities too. Nullable so existing rows upgrade safely, and
            // so an item survives its activity_id being cleared.
            $table->foreignId('weekly_routine_id')->nullable()->after('weekly_plan_id')
                ->constrained('weekly_routines')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('weekly_plan_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('weekly_routine_id');
        });
    }
};
