<?php

use App\Enums\ResponseItemType;
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
        Schema::create('application_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained();
            $table->enum('item_type', ResponseItemType::values());
            $table->unsignedBigInteger('item_id');
            $table->boolean('granted')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_responses');
    }
};
