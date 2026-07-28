<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the merged child feed. The three sources are normalised into a
 * single shape by JournalFeed — which is where the viewer-dependent bits
 * ("Me", privacy, download URLs) are decided — so this resource only fixes
 * the wire format.
 */
class FeedItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
