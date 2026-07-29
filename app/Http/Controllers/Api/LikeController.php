<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LikeRequest;
use App\Models\Comment;
use App\Models\Guardian;
use App\Models\JournalEntry;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class LikeController extends Controller
{
    /** Idempotent: liking twice is one like, and both calls succeed. */
    public function store(LikeRequest $request): JsonResponse
    {
        $likeable = $request->likeable();
        $this->authorize('view', $likeable);

        $likeable->likes()->firstOrCreate(
            ['guardian_id' => $this->guardian()->getKey()],
            ['created_at' => now()],
        );

        return response()->json(['data' => $this->state($request, $likeable, likedByMe: true)], 201);
    }

    public function destroy(LikeRequest $request): Response
    {
        $likeable = $request->likeable();
        $this->authorize('view', $likeable);

        $likeable->likes()->where('guardian_id', $this->guardian()->getKey())->delete();

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function state(LikeRequest $request, Media|Comment|JournalEntry $likeable, bool $likedByMe): array
    {
        return [
            'likeable_type' => $request->string('likeable_type')->value(),
            'likeable_id' => $likeable->getKey(),
            'likes_count' => $likeable->likes()->count(),
            'liked_by_me' => $likedByMe,
        ];
    }

    private function guardian(): Guardian
    {
        return auth('guardian')->user();
    }
}
