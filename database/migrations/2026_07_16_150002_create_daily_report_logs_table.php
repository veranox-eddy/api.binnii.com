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
        Schema::create('daily_report_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained();
            $table->string('action', 60); // created / sent / reopened / edited / deleted
            $table->foreignId('actor_id')->nullable()->constrained('users');
            $table->dateTime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_report_logs');
    }
};
