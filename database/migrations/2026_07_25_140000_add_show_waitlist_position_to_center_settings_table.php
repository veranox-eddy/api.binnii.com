<?php

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
        Schema::table('center_settings', function (Blueprint $table) {
            // me-registration.html gear menu → "Show waitlist position"
            // (schema doc §B, v1.7).
            $table->boolean('show_waitlist_position')->default(false)->after('parents_can_mark_absent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('center_settings', function (Blueprint $table) {
            $table->dropColumn('show_waitlist_position');
        });
    }
};
