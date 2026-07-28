<?php

namespace Tests\Feature\Api;

use App\Enums\MediaStatus;
use App\Models\Center;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaDownloadApiTest extends TestCase
{
    use RefreshDatabase;

    private Center $center;

    private Classroom $classroom;

    private Guardian $guardian;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->center = Center::factory()->create();
        $this->classroom = Classroom::factory()->create(['center_id' => $this->center->id]);
        $this->guardian = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->child = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($this->guardian, $this->child, ['has_full_photo_access' => true]);
    }

    public function test_download_requires_a_token(): void
    {
        $media = $this->media([$this->child]);

        $this->getJson("/api/v1/media/{$media->id}/download")->assertUnauthorized();
    }

    public function test_a_released_photo_of_their_own_child_streams(): void
    {
        $media = $this->media([$this->child]);

        $response = $this->actingAsGuardian($this->guardian)
            ->get("/api/v1/media/{$media->id}/download")
            ->assertOk();

        $this->assertSame('photo-bytes', $response->streamedContent());
    }

    public function test_a_draft_photo_is_not_found(): void
    {
        $media = $this->media([$this->child], MediaStatus::Draft);

        $this->actingAsGuardian($this->guardian)
            ->get("/api/v1/media/{$media->id}/download")
            ->assertNotFound();
    }

    public function test_another_familys_photo_is_not_found(): void
    {
        $stranger = Child::factory()->create(['center_id' => $this->center->id]);
        $media = $this->media([$stranger]);

        $this->actingAsGuardian($this->guardian)
            ->get("/api/v1/media/{$media->id}/download")
            ->assertNotFound();
    }

    public function test_a_group_photo_needs_full_photo_access(): void
    {
        $classmate = Child::factory()->create(['center_id' => $this->center->id]);
        $group = $this->media([$this->child, $classmate]);

        $limited = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($limited, $this->child, ['has_full_photo_access' => false]);

        $this->actingAsGuardian($limited)
            ->get("/api/v1/media/{$group->id}/download")
            ->assertForbidden();

        // A photo of their child alone is always theirs to keep.
        $solo = $this->media([$this->child]);
        $this->actingAsGuardian($limited)
            ->get("/api/v1/media/{$solo->id}/download")
            ->assertOk();

        // And the guardian with full access gets the group photo.
        $this->actingAsGuardian($this->guardian)
            ->get("/api/v1/media/{$group->id}/download")
            ->assertOk();
    }

    public function test_a_missing_file_is_not_found(): void
    {
        $media = $this->media([$this->child]);
        Storage::disk('public')->delete($media->file_path);

        $this->actingAsGuardian($this->guardian)
            ->get("/api/v1/media/{$media->id}/download")
            ->assertNotFound();
    }

    /** @param  array<int, Child>  $children */
    private function media(array $children, MediaStatus $status = MediaStatus::Sent): Media
    {
        $media = Media::factory()->create([
            'center_id' => $this->center->id,
            'classroom_id' => $this->classroom->id,
            'status' => $status,
            'sent_at' => $status === MediaStatus::Sent ? now() : null,
        ]);

        $media->children()->attach(collect($children)->pluck('id'));
        Storage::disk('public')->put($media->file_path, 'photo-bytes');

        return $media;
    }
}
