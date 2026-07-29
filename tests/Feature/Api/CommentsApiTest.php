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
use App\Models\Like;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentsApiTest extends TestCase
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

    public function test_comment_lists_require_a_token(): void
    {
        $media = $this->sentMedia();
        $entry = $this->journalEntry();

        $this->getJson("/api/v1/media/{$media->id}/comments")->assertUnauthorized();
        $this->getJson("/api/v1/journal-entries/{$entry->id}/comments")->assertUnauthorized();
        $this->postJson('/api/v1/comments', [])->assertUnauthorized();
    }

    public function test_a_guardian_can_comment_on_their_childs_photo(): void
    {
        $media = $this->sentMedia();

        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/comments', [
                'child_id' => $this->child->id,
                'media_id' => $media->id,
                'body' => 'What a great day!',
            ])
            ->assertCreated()
            ->assertJsonPath('data.body', 'What a great day!')
            ->assertJsonPath('data.author_type', 'guardian')
            ->assertJsonPath('data.is_mine', true);

        $this->assertDatabaseHas('comments', [
            'media_id' => $media->id,
            'guardian_id' => $this->guardian->id,
            'child_id' => $this->child->id,
            'thread_subject' => CommentThreadSubject::Post->value,
            'status' => CommentStatus::Inbox->value,
        ]);
    }

    public function test_a_guardian_can_comment_on_a_family_journal_entry(): void
    {
        $entry = $this->journalEntry();

        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/comments', [
                'child_id' => $this->child->id,
                'journal_entry_id' => $entry->id,
                'body' => 'He looks so proud!',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('comments', [
            'journal_entry_id' => $entry->id,
            'guardian_id' => $this->guardian->id,
        ]);
    }

    public function test_another_familys_photo_or_entry_cannot_be_commented_on(): void
    {
        [$stranger, $theirChild] = $this->otherFamily();
        $theirMedia = $this->sentMedia($theirChild);
        $theirEntry = $this->journalEntry($theirChild, $stranger);

        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/comments', [
                'child_id' => $this->child->id,
                'media_id' => $theirMedia->id,
                'body' => 'Hi',
            ])
            ->assertNotFound();

        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/comments', [
                'child_id' => $this->child->id,
                'journal_entry_id' => $theirEntry->id,
                'body' => 'Hi',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_a_comment_needs_exactly_one_target(): void
    {
        $media = $this->sentMedia();
        $entry = $this->journalEntry();

        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/comments', ['child_id' => $this->child->id, 'body' => 'Hi'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['media_id', 'journal_entry_id']);

        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/comments', [
                'child_id' => $this->child->id,
                'body' => 'Hi',
                'media_id' => $media->id,
                'journal_entry_id' => $entry->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['media_id']);
    }

    public function test_the_named_child_must_be_the_one_in_the_photo(): void
    {
        $media = $this->sentMedia();
        $sibling = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($this->guardian, $sibling);

        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/comments', [
                'child_id' => $sibling->id,
                'media_id' => $media->id,
                'body' => 'Hi',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['child_id']);
    }

    public function test_the_list_returns_threads_with_like_counts(): void
    {
        $media = $this->sentMedia();

        $root = $this->comment($media, $this->guardian, 'First day photos!');
        $reply = Comment::create([
            'parent_id' => $root->id,
            'media_id' => $media->id,
            'child_id' => $this->child->id,
            'guardian_id' => null, // the center's reply
            'thread_subject' => CommentThreadSubject::Post,
            'body' => 'She had a wonderful morning.',
            'status' => CommentStatus::Inbox,
        ]);

        $coParent = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($coParent, $this->child);
        Like::create(['guardian_id' => $coParent->id, 'likeable_type' => 'comment', 'likeable_id' => $root->id, 'created_at' => now()]);
        Like::create(['guardian_id' => $this->guardian->id, 'likeable_type' => 'comment', 'likeable_id' => $root->id, 'created_at' => now()]);

        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/media/{$media->id}/comments")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $root->id)
            ->assertJsonPath('data.0.likes_count', 2)
            ->assertJsonPath('data.0.liked_by_me', true)
            ->assertJsonPath('data.0.replies.0.id', $reply->id)
            ->assertJsonPath('data.0.replies.0.author_type', 'center')
            ->assertJsonPath('data.0.replies.0.author_name', 'Center');
    }

    public function test_a_journal_entry_thread_is_scoped_to_the_family(): void
    {
        $entry = $this->journalEntry();
        $this->comment($entry, $this->guardian, 'So sweet');

        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/journal-entries/{$entry->id}/comments")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        [$stranger] = $this->otherFamily();

        $this->actingAsGuardian($stranger)
            ->getJson("/api/v1/journal-entries/{$entry->id}/comments")
            ->assertNotFound();
    }

    public function test_replies_must_stay_on_the_same_item(): void
    {
        $media = $this->sentMedia();
        $other = $this->sentMedia();
        $root = $this->comment($other, $this->guardian, 'Elsewhere');

        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/comments', [
                'child_id' => $this->child->id,
                'media_id' => $media->id,
                'parent_id' => $root->id,
                'body' => 'Cross-thread reply',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_only_the_author_may_delete_a_comment(): void
    {
        $media = $this->sentMedia();
        $mine = $this->comment($media, $this->guardian, 'Mine');

        $coParent = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($coParent, $this->child);
        $theirs = $this->comment($media, $coParent, 'Theirs');

        $center = Comment::create([
            'media_id' => $media->id,
            'child_id' => $this->child->id,
            'guardian_id' => null,
            'thread_subject' => CommentThreadSubject::Post,
            'body' => 'From the center',
            'status' => CommentStatus::Inbox,
        ]);

        $this->actingAsGuardian($this->guardian)
            ->deleteJson("/api/v1/comments/{$theirs->id}")
            ->assertForbidden();
        $this->actingAsGuardian($this->guardian)
            ->deleteJson("/api/v1/comments/{$center->id}")
            ->assertForbidden();

        $this->actingAsGuardian($this->guardian)
            ->deleteJson("/api/v1/comments/{$mine->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('comments', ['id' => $mine->id]);
        $this->assertDatabaseHas('comments', ['id' => $theirs->id]);
    }

    public function test_deleting_a_comment_takes_its_replies_and_likes_with_it(): void
    {
        $entry = $this->journalEntry();
        $root = $this->comment($entry, $this->guardian, 'Root');
        $reply = Comment::create([
            'parent_id' => $root->id,
            'journal_entry_id' => $entry->id,
            'child_id' => $this->child->id,
            'guardian_id' => null,
            'thread_subject' => CommentThreadSubject::Post,
            'body' => 'Reply',
            'status' => CommentStatus::Inbox,
        ]);
        Like::create(['guardian_id' => $this->guardian->id, 'likeable_type' => 'comment', 'likeable_id' => $root->id, 'created_at' => now()]);
        Like::create(['guardian_id' => $this->guardian->id, 'likeable_type' => 'comment', 'likeable_id' => $reply->id, 'created_at' => now()]);

        $this->actingAsGuardian($this->guardian)
            ->deleteJson("/api/v1/comments/{$root->id}")
            ->assertNoContent();

        $this->assertDatabaseCount('comments', 0);
        $this->assertDatabaseCount('likes', 0);
    }

    public function test_a_comment_out_of_scope_reads_as_missing_not_forbidden(): void
    {
        [$stranger, $theirChild] = $this->otherFamily();
        $theirs = $this->comment($this->journalEntry($theirChild, $stranger), $stranger, 'Private family talk');

        $this->actingAsGuardian($this->guardian)
            ->deleteJson("/api/v1/comments/{$theirs->id}")
            ->assertNotFound();
    }

    private function sentMedia(?Child $child = null): Media
    {
        $media = Media::factory()->create([
            'center_id' => $this->center->id,
            'classroom_id' => $this->classroom->id,
            'status' => MediaStatus::Sent,
            'sent_at' => now(),
        ]);

        $media->children()->attach($child ?? $this->child);

        return $media;
    }

    private function journalEntry(?Child $child = null, ?Guardian $guardian = null): JournalEntry
    {
        return JournalEntry::factory()->create([
            'child_id' => ($child ?? $this->child)->id,
            'guardian_id' => ($guardian ?? $this->guardian)->id,
        ]);
    }

    private function comment(Media|JournalEntry $target, Guardian $author, string $body): Comment
    {
        return Comment::create([
            'media_id' => $target instanceof Media ? $target->id : null,
            'journal_entry_id' => $target instanceof JournalEntry ? $target->id : null,
            'child_id' => $target instanceof JournalEntry ? $target->child_id : $this->child->id,
            'guardian_id' => $author->id,
            'thread_subject' => CommentThreadSubject::Post,
            'body' => $body,
            'status' => CommentStatus::Inbox,
        ]);
    }

    /** @return array{0: Guardian, 1: Child} */
    private function otherFamily(): array
    {
        $stranger = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $theirChild = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($stranger, $theirChild);

        return [$stranger, $theirChild];
    }
}
