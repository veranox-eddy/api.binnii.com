<?php

use App\Enums\ConversationType;
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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained();
            $table->string('subject');
            $table->enum('type', ConversationType::values());
            $table->foreignId('created_by')->constrained('users');
            $table->boolean('shared_with_teachers')->default(false);
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained();
            $table->string('participant_type', 60); // user / guardian (morph map)
            $table->unsignedBigInteger('participant_id');
            $table->string('role', 40)->nullable();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained();
            $table->string('sender_type', 60); // user / guardian (morph map)
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->dateTime('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
