<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Archiving a conversation (db-conversation.html's Archive / Submit &
 * Archive). Orthogonal to type/channel — an archived thread keeps all of its
 * data and only leaves the inbox — so it is a nullable timestamp rather than
 * a status enum. Schema doc §K, v1.8.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dateTime('archived_at')->nullable()->after('shared_with_teachers');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
