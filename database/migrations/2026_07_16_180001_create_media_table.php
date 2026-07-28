<?php

use App\Enums\MediaStatus;
use App\Enums\MediaType;
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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained();
            $table->foreignId('classroom_id')->nullable()->constrained();
            $table->foreignId('uploaded_by')->nullable()->constrained('staff');
            $table->enum('media_type', MediaType::values());
            $table->string('file_path');
            $table->text('caption')->nullable();
            $table->enum('status', MediaStatus::values())->default(MediaStatus::Draft->value);
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('occurred_at')->nullable();
            $table->timestamps();
        });

        Schema::create('media_child', function (Blueprint $table) {
            $table->foreignId('media_id')->constrained('media');
            $table->foreignId('child_id')->constrained('children');
            $table->primary(['media_id', 'child_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_child');
        Schema::dropIfExists('media');
    }
};
