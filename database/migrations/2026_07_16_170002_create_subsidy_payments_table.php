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
        Schema::create('subsidy_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subsidy_agreement_id')->constrained();
            $table->string('payment_period', 40);
            $table->string('description', 190)->nullable();
            $table->decimal('estimated_amount', 10, 2)->nullable();
            $table->decimal('received_amount', 10, 2)->nullable();
            // Generated column per the schema doc.
            $table->decimal('difference', 10, 2)->nullable()
                ->storedAs('received_amount - estimated_amount');
            $table->date('payment_date')->nullable();
            $table->string('action_taken', 120)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subsidy_payments');
    }
};
