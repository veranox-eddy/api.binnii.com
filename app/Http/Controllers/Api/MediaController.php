<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Center photos are not public URLs — who may see a photo depends on
     * which children it is tagged to, so every download goes through the
     * policy and is streamed from the disk. One file at a time, matching
     * the parent app's FAQ.
     */
    public function download(Media $media): StreamedResponse
    {
        $this->authorize('download', $media);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($media->file_path), 404);

        return $disk->download($media->file_path, basename($media->file_path));
    }
}
