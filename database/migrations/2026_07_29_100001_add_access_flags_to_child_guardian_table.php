<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * API_02 M2. Per-child access flags for the parent Crew screen (API_07).
 * `relationship` already exists as a varchar and keeps holding the Crew
 * relationship label. Photo access defaults to true so existing links are
 * unchanged by the migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('child_guardian', function (Blueprint $table) {
            $table->boolean('is_account_admin')->default(false)->after('relationship');
            $table->boolean('has_full_photo_access')->default(true)->after('is_account_admin');
            $table->string('nickname', 80)->nullable()->after('has_full_photo_access');
        });
    }

    public function down(): void
    {
        Schema::table('child_guardian', function (Blueprint $table) {
            $table->dropColumn(['is_account_admin', 'has_full_photo_access', 'nickname']);
        });
    }
};
