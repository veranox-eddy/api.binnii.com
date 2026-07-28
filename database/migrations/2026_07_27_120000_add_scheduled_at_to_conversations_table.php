<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schedule Send (db-compose.html's Schedule Send + the Scheduled tab on the
 * message centre). A future scheduled_at means the thread has not been
 * released yet and shows only on Scheduled; NULL or a past value reads as
 * sent. Orthogonal to type/channel/archived_at. Schema doc §K, v1.9.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dateTime('scheduled_at')->nullable()->after('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('scheduled_at');
        });
    }
};
