<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Not in API_02, but required by the shared database: the framework's
 * `password_reset_tokens` table is keyed by email alone, and this DB holds
 * both staff and guardians. A teacher who is also a parent at the center
 * would share one row for two identities — each side's reset link would
 * cancel the other's, and either token could be redeemed against the wrong
 * account. The `guardians` broker (config/auth.php) points here instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_password_reset_tokens');
    }
};
