<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\ChildController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\CrewController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MilestoneController;
use App\Http\Controllers\Api\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Parent API (v1)
|--------------------------------------------------------------------------
|
| Guardian-facing endpoints only. Everything behind `auth:guardian` is
| scoped to the authenticated guardian's children via `child_guardian` —
| see the policies, not just the queries.
|
*/

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/activation/validate', [AuthController::class, 'validateActivation']);
    Route::post('auth/activation/complete', [AuthController::class, 'completeActivation']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:guardian')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // The profile update carries a photo, and PHP does not parse
        // multipart bodies on a real PUT — the SPA posts it with
        // `_method=PUT`, which Laravel routes here.
        Route::get('children', [ChildController::class, 'index']);
        Route::get('children/{child}', [ChildController::class, 'show']);
        Route::put('children/{child}', [ChildController::class, 'update']);

        Route::get('children/{child}/crew', [CrewController::class, 'index']);
        Route::post('children/{child}/crew', [CrewController::class, 'store']);
        Route::get('children/{child}/crew/{guardian}', [CrewController::class, 'show']);
        Route::put('children/{child}/crew/{guardian}', [CrewController::class, 'update']);
        Route::delete('children/{child}/crew/{guardian}', [CrewController::class, 'destroy']);

        Route::get('children/{child}/calendar', [CalendarController::class, 'index']);

        Route::get('children/{child}/milestones', [MilestoneController::class, 'index']);
        Route::put('children/{child}/milestones', [MilestoneController::class, 'upsert']);
        Route::post('children/{child}/milestones/custom', [MilestoneController::class, 'storeCustom']);

        Route::get('children/{child}/journal', [JournalController::class, 'feed']);
        Route::get('children/{child}/entries', [JournalController::class, 'entries']);
        Route::post('children/{child}/journal-entries', [JournalController::class, 'store']);

        Route::get('journal-entries/{journalEntry}', [JournalController::class, 'show']);
        Route::put('journal-entries/{journalEntry}', [JournalController::class, 'update']);
        Route::delete('journal-entries/{journalEntry}', [JournalController::class, 'destroy']);
        Route::post('journal-entries/{journalEntry}/share', [JournalController::class, 'share']);

        // Named: the feed embeds this URL for every center photo instead of
        // a public disk URL.
        Route::get('media/{media}/download', [MediaController::class, 'download'])->name('api.media.download');

        Route::get('media/{media}/comments', [CommentController::class, 'forMedia']);
        Route::get('journal-entries/{journalEntry}/comments', [CommentController::class, 'forJournalEntry']);
        Route::post('comments', [CommentController::class, 'store']);
        Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

        // DELETE carries the same body as POST — a like has no id of its
        // own the SPA ever sees.
        Route::post('likes', [LikeController::class, 'store']);
        Route::delete('likes', [LikeController::class, 'destroy']);

        Route::get('settings', [SettingsController::class, 'show']);
        Route::put('settings/profile', [SettingsController::class, 'updateProfile']);
        Route::put('settings/email', [SettingsController::class, 'updateEmail']);
        Route::put('settings/password', [SettingsController::class, 'updatePassword']);
        Route::put('settings/notifications', [SettingsController::class, 'updateNotifications']);

        Route::get('conversations', [ConversationController::class, 'index']);
        Route::post('conversations', [ConversationController::class, 'store']);
        Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
        Route::post('conversations/{conversation}/reply', [ConversationController::class, 'reply']);
        Route::post('conversations/{conversation}/archive', [ConversationController::class, 'archive']);
    });
});
