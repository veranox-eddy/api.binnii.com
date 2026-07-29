<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * API_06 wants comments on guardian journal entries, but neither the admin
 * schema nor API_02 gave `comments` a column that can point at one. Additive
 * and nullable, mirroring `media_id`, so the admin app is unaffected —
 * schema doc §K updated to match (owner approved this deviation from
 * API_02's "only guardians / child_guardian are altered").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')
                ->nullable()
                ->after('media_id')
                ->constrained('journal_entries');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
        });
    }
};
