<?php

use App\Enums\CommentStatus;
use App\Enums\CommentThreadSubject;
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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('comments');
            $table->foreignId('media_id')->nullable()->constrained('media');
            $table->foreignId('child_id')->nullable()->constrained('children');
            $table->foreignId('guardian_id')->nullable()->constrained();
            $table->enum('thread_subject', CommentThreadSubject::values());
            $table->text('body');
            $table->enum('status', CommentStatus::values())->default(CommentStatus::Inbox->value);
            $table->timestamps();
        });

        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_id')->constrained();
            $table->string('likeable_type', 60); // media / comment (morph map)
            $table->unsignedBigInteger('likeable_id');
            $table->dateTime('created_at');
            $table->unique(['guardian_id', 'likeable_type', 'likeable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('likes');
        Schema::dropIfExists('comments');
    }
};
