<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Guardian;
use App\Models\JournalEntry;
use App\Models\Media;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Must stay identical to the admin console's map (plus the
        // parent-only `journal_entry`): both apps read the same
        // participant_type / sender_type / likeable_type columns, so an
        // alias that differed here would silently orphan those rows.
        Relation::enforceMorphMap([
            'user' => User::class,
            'guardian' => Guardian::class,
            'staff' => Staff::class,
            'media' => Media::class,
            'comment' => Comment::class,
            'journal_entry' => JournalEntry::class,
        ]);

        // Keyed by guardian rather than IP: a family behind one NAT would
        // otherwise share a single bucket. Unauthenticated hits (login,
        // activation) fall back to the IP.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user('guardian')?->getAuthIdentifier() ?: $request->ip()));
    }
}
