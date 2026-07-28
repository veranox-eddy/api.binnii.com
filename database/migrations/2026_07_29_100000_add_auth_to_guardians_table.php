<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * API_02 M1. Guardians gain a login for the parent portal. Every column is
 * additive and nullable/defaulted so the admin console keeps writing
 * guardian rows exactly as before — `password` stays null until the parent
 * completes activation, which is also what `canLogIn()` gates on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('receive_fewer_emails')->default(false);
            $table->string('email_language', 10)->default('en');
        });
    }

    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropColumn([
                'password', 'remember_token', 'email_verified_at',
                'last_login_at', 'receive_fewer_emails', 'email_language',
            ]);
        });
    }
};
