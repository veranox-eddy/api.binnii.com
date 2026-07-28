<?php

use App\Enums\MediaType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * API_02 M3. Guardian-authored journal entries — deliberately NOT the staff
 * `media` table, whose uploaded_by=staff / status draft|sent semantics do
 * not apply here. These rows are never visible to the center.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children');
            $table->foreignId('guardian_id')->constrained('guardians');
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->date('entry_date');
            $table->boolean('is_private')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_milestone')->default(false);
            $table->timestamps();
            $table->index(['child_id', 'entry_date']);
        });

        Schema::create('journal_entry_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->enum('media_type', MediaType::values());
            $table->string('file_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_media');
        Schema::dropIfExists('journal_entries');
    }
};
