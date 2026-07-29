<?php

namespace Tests\Feature\Api;

use App\Models\Center;
use App\Models\Child;
use App\Models\Conversation;
use App\Models\Guardian;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessagesApiTest extends TestCase
{
    use RefreshDatabase;

    private Center $center;

    private User $director;

    private Guardian $guardian;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->center = Center::factory()->create(['name' => 'Sunrise House']);
        $this->director = User::factory()->create([
            'organization_id' => $this->center->organization_id,
            'name' => 'Dana Director',
        ]);
        $this->director->centers()->attach($this->center);

        $this->guardian = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->child = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($this->guardian, $this->child);
    }

    public function test_the_inbox_requires_a_token(): void
    {
        $this->getJson('/api/v1/conversations')->assertUnauthorized();
    }

    public function test_the_inbox_lists_only_the_guardians_threads_newest_first(): void
    {
        $old = $this->centerThread('Fall schedule', at: now()->subDays(3));
        $new = $this->centerThread('Field trip Friday', at: now()->subHour());
        $archived = $this->centerThread('Old news', at: now()->subDay());
        $archived->update(['archived_at' => now()]);

        $otherGuardian = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $notMine = $this->centerThread('Private to someone else', for: $otherGuardian);

        $response = $this->actingAsGuardian($this->guardian)
            ->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertSame([$new->id, $old->id], array_column($response->json('data'), 'id'));
        $this->assertNotContains($archived->id, array_column($response->json('data'), 'id'));
        $this->assertNotContains($notMine->id, array_column($response->json('data'), 'id'));

        $row = $response->json('data.0');
        $this->assertSame('Field trip Friday', $row['subject']);
        $this->assertSame('Dana Director', $row['from']);
        $this->assertTrue($row['unread']);
        $this->assertNotNull($row['time']);
    }

    public function test_reading_a_thread_marks_inbound_messages_read(): void
    {
        $thread = $this->centerThread('Nap schedule');
        $inbound = $thread->messages()->first();

        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/conversations/{$thread->id}")
            ->assertOk()
            ->assertJsonPath('data.subject', 'Nap schedule')
            ->assertJsonPath('data.messages.0.sender_name', 'Dana Director')
            ->assertJsonPath('data.messages.0.sender_type', 'user');

        $this->assertNotNull($inbound->fresh()->read_at);

        $this->actingAsGuardian($this->guardian)
            ->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.unread', false);
    }

    public function test_a_thread_outside_the_guardians_inbox_reads_as_missing(): void
    {
        $otherGuardian = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $theirs = $this->centerThread('Not yours', for: $otherGuardian);

        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/conversations/{$theirs->id}")
            ->assertNotFound();
    }

    public function test_composing_creates_a_center_routed_thread_from_the_guardian(): void
    {
        Storage::fake('public');

        $response = $this->actingAsGuardian($this->guardian)
            ->post('/api/v1/conversations', [
                'child_id' => $this->child->id,
                'send_to' => 'director_teacher',
                'subject' => 'About pickup',
                'body' => 'Grandma will pick her up today.',
                'attachments' => [UploadedFile::fake()->image('note.png')],
            ])
            ->assertCreated()
            ->assertJsonPath('data.subject', 'About pickup')
            ->assertJsonPath('data.messages.0.sender_name', 'Me')
            ->assertJsonPath('data.messages.0.sender_type', 'guardian')
            ->assertJsonPath('data.messages.0.attachments.0.original_name', 'note.png');

        $conversation = Conversation::findOrFail($response->json('data.id'));

        $this->assertSame($this->center->id, $conversation->center_id);
        $this->assertTrue($conversation->shared_with_teachers);
        // created_by must be a real staff user; the guardian authors the
        // thread through participants + the message sender.
        $this->assertSame($this->director->id, $conversation->created_by);
        $this->assertTrue($conversation->hasGuardianParticipant($this->guardian));
        $this->assertDatabaseHas('conversation_participants', [
            'conversation_id' => $conversation->id,
            'participant_type' => 'guardian',
            'participant_id' => $this->guardian->id,
            'role' => 'sender',
        ]);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_type' => 'guardian',
            'sender_id' => $this->guardian->id,
        ]);
        Storage::disk('public')->assertExists(
            $conversation->messages->first()->attachments->first()->file_path,
        );
    }

    public function test_director_only_keeps_the_thread_off_the_teachers(): void
    {
        $response = $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/conversations', [
                'child_id' => $this->child->id,
                'send_to' => 'director_only',
                'subject' => 'Sensitive',
                'body' => 'For the director only.',
            ])
            ->assertCreated();

        $this->assertFalse(Conversation::findOrFail($response->json('data.id'))->shared_with_teachers);
    }

    public function test_composing_for_a_child_that_is_not_yours_is_a_404(): void
    {
        $stranger = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $theirChild = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($stranger, $theirChild);

        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/conversations', [
                'child_id' => $theirChild->id,
                'send_to' => 'director_only',
                'subject' => 'Hello',
                'body' => 'Hello',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_a_participant_can_reply_but_an_outsider_cannot(): void
    {
        $thread = $this->centerThread('Nap schedule');

        $this->actingAsGuardian($this->guardian)
            ->postJson("/api/v1/conversations/{$thread->id}/reply", ['body' => 'Thanks!'])
            ->assertCreated()
            ->assertJsonPath('data.sender_name', 'Me');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $thread->id,
            'sender_type' => 'guardian',
            'sender_id' => $this->guardian->id,
            'body' => 'Thanks!',
        ]);

        $outsider = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);

        $this->actingAsGuardian($outsider)
            ->postJson("/api/v1/conversations/{$thread->id}/reply", ['body' => 'Let me in'])
            ->assertForbidden();
    }

    public function test_archiving_hides_the_thread_and_is_participant_only(): void
    {
        $thread = $this->centerThread('Done deal');

        $outsider = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->actingAsGuardian($outsider)
            ->postJson("/api/v1/conversations/{$thread->id}/archive")
            ->assertForbidden();

        $this->actingAsGuardian($this->guardian)
            ->postJson("/api/v1/conversations/{$thread->id}/archive")
            ->assertNoContent();

        $this->assertNotNull($thread->fresh()->archived_at);

        $this->actingAsGuardian($this->guardian)
            ->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_the_attachment_count_is_capped(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->postJson('/api/v1/conversations', [
                'child_id' => $this->child->id,
                'send_to' => 'director_only',
                'subject' => 'Too many files',
                'body' => 'Body',
                'attachments' => ['1', '2', '3', '4'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attachments']);
    }

    /** A center-authored thread the guardian participates in. */
    private function centerThread(string $subject, ?Guardian $for = null, ?\DateTimeInterface $at = null): Conversation
    {
        $for ??= $this->guardian;
        $at ??= now();

        $conversation = Conversation::factory()->create([
            'center_id' => $this->center->id,
            'subject' => $subject,
            'created_by' => $this->director->id,
        ]);

        $conversation->participants()->create([
            'participant_type' => 'user',
            'participant_id' => $this->director->id,
            'role' => 'sender',
        ]);
        $conversation->participants()->create([
            'participant_type' => 'guardian',
            'participant_id' => $for->id,
            'role' => 'recipient',
        ]);

        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'user',
            'sender_id' => $this->director->id,
            'created_at' => $at,
        ]);

        return $conversation;
    }
}
