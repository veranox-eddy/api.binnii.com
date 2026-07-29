<?php

namespace Tests\Feature\Api;

use App\Enums\CommentStatus;
use App\Enums\CommentThreadSubject;
use App\Enums\MediaStatus;
use App\Models\Center;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\Comment;
use App\Models\Guardian;
use App\Models\JournalEntry;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikesApiTest extends TestCase
{
    use RefreshDatabase;

    private Center $center;

    private Classroom $classroom;

    private Guardian $guardian;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->center = Center::factory()->create(['timezone' => 'UTC']);
        $this->classroom = Classroom::factory()->create([
            'center_id' => $this->center->id,
            'photo_sharing_enabled' => true,
        ]);
        $this->guardian = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->child = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($this->guardian, $this->child);
    }

    public function test_likes_require_a_token(): void
    {
        $this->postJson('/api/v1/likes', [])->assertUnauthorized();
        $this->deleteJson('/api/v1/likes', [])->assertUnauthorized();
    }

    public function test_liking_a_photo_is_idempotent(): void
    {
        $media = $this->sentMedia();

        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/likes', ['likeable_type' => 'media', 'likeable_id' => $media->id])
            ->assertCreated()
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.liked_by_me', true);

        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/likes', ['likeable_type' => 'media', 'likeable_id' => $media->id])
            ->assertCreated()
            ->assertJsonPath('data.likes_count', 1);

        $this->assertDatabaseCount('likes', 1);
    }

    public function test_a_journal_entry_and_a_comment_can_be_liked(): void
    {
        $entry = $this->journalEntry();
        $comment = Comment::create([
            'journal_entry_id' => $entry->id,
            'child_id' => $this->child->id,
            'guardian_id' => $this->guardian->id,
            'thread_subject' => CommentThreadSubject::Post,
            'body' => 'A milestone!',
            'status' => CommentStatus::Inbox,
        ]);

        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/likes', ['likeable_type' => 'journal_entry', 'likeable_id' => $entry->id])
            ->assertCreated();
        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/likes', ['likeable_type' => 'comment', 'likeable_id' => $comment->id])
            ->assertCreated();

        $this->assertDatabaseHas('likes', ['likeable_type' => 'journal_entry', 'likeable_id' => $entry->id]);
        $this->assertDatabaseHas('likes', ['likeable_type' => 'comment', 'likeable_id' => $comment->id]);
    }

    public function test_unliking_removes_the_like_and_is_safe_to_repeat(): void
    {
        $media = $this->sentMedia();
        $body = ['likeable_type' => 'media', 'likeable_id' => $media->id];

        $this->actingAsGuardian($this->guardian)->postJson('/api/v1/likes', $body)->assertCreated();
        $this->actingAsGuardian($this->guardian)->deleteJson('/api/v1/likes', $body)->assertNoContent();
        $this->assertDatabaseCount('likes', 0);

        $this->actingAsGuardian($this->guardian)->deleteJson('/api/v1/likes', $body)->assertNoContent();
    }

    public function test_only_the_guardians_own_like_is_removed(): void
    {
        $media = $this->sentMedia();
        $coParent = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($coParent, $this->child);

        $body = ['likeable_type' => 'media', 'likeable_id' => $media->id];
        $this->actingAsGuardian($this->guardian)->postJson('/api/v1/likes', $body)->assertCreated();
        $this->actingAsGuardian($coParent)->postJson('/api/v1/likes', $body)->assertCreated();

        $this->actingAsGuardian($this->guardian)->deleteJson('/api/v1/likes', $body)->assertNoContent();

        $this->assertDatabaseCount('likes', 1);
        $this->assertDatabaseHas('likes', ['guardian_id' => $coParent->id]);
    }

    public function test_another_familys_content_cannot_be_liked(): void
    {
        $stranger = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $theirChild = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($stranger, $theirChild);
        $theirEntry = JournalEntry::factory()->create([
            'child_id' => $theirChild->id,
            'guardian_id' => $stranger->id,
        ]);

        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/likes', ['likeable_type' => 'journal_entry', 'likeable_id' => $theirEntry->id])
            ->assertNotFound();

        $this->assertDatabaseCount('likes', 0);
    }

    public function test_an_unreleased_photo_cannot_be_liked(): void
    {
        $draft = Media::factory()->create([
            'center_id' => $this->center->id,
            'classroom_id' => $this->classroom->id,
            'status' => MediaStatus::Draft,
        ]);
        $draft->children()->attach($this->child);

        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/likes', ['likeable_type' => 'media', 'likeable_id' => $draft->id])
            ->assertNotFound();
    }

    public function test_the_likeable_type_is_validated(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/likes', ['likeable_type' => 'staff', 'likeable_id' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['likeable_type']);
    }

    public function test_like_state_shows_up_on_the_journal_entry(): void
    {
        $entry = $this->journalEntry();
        $body = ['likeable_type' => 'journal_entry', 'likeable_id' => $entry->id];

        $this->actingAsGuardian($this->guardian)->postJson('/api/v1/likes', $body)->assertCreated();

        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/journal-entries/{$entry->id}")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.liked_by_me', true);

        $this->actingAsGuardian($this->guardian)->deleteJson('/api/v1/likes', $body)->assertNoContent();

        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/journal-entries/{$entry->id}")
            ->assertOk()
            ->assertJsonPath('data.likes_count', 0)
            ->assertJsonPath('data.liked_by_me', false);
    }

    private function sentMedia(): Media
    {
        $media = Media::factory()->create([
            'center_id' => $this->center->id,
            'classroom_id' => $this->classroom->id,
            'status' => MediaStatus::Sent,
            'sent_at' => now(),
        ]);

        $media->children()->attach($this->child);

        return $media;
    }

    private function journalEntry(): JournalEntry
    {
        return JournalEntry::factory()->create([
            'child_id' => $this->child->id,
            'guardian_id' => $this->guardian->id,
        ]);
    }
}
