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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained();
            // NULL classroom = the "Administrative logins" row.
            $table->foreignId('classroom_id')->nullable()->constrained();
            $table->boolean('new_messages')->default(true);
            $table->boolean('new_comments')->default(true);
            $table->boolean('new_likes')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
