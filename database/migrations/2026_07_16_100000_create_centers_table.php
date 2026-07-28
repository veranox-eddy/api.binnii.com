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
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained();
            $table->string('name', 150);
            $table->string('external_ref', 30)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('phone_country_code', 8)->nullable();
            $table->string('timezone', 64);
            $table->string('tax_id', 50)->nullable();
            $table->string('address_line1', 190)->nullable();
            $table->string('address_line2', 120)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 80)->nullable();
            $table->string('zip', 20)->nullable();
            $table->integer('licensed_capacity')->nullable();
            $table->integer('desired_capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centers');
    }
};
