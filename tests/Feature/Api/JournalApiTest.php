<?php

namespace Tests\Feature\Api;

use App\Enums\EntryType;
use App\Enums\MediaStatus;
use App\Enums\ReportStatus;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\DailyReport;
use App\Models\Entry;
use App\Models\Guardian;
use App\Models\JournalEntry;
use App\Models\JournalEntryMedia;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JournalApiTest extends TestCase
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
        $this->linkGuardianToChild($this->guardian, $this->child, ['has_full_photo_access' => true]);
    }

    public function test_the_feed_requires_a_token(): void
    {
        $this->getJson("/api/v1/children/{$this->child->id}/journal")->assertUnauthorized();
    }

    public function test_another_guardians_child_has_no_feed(): void
    {
        $stranger = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $theirChild = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($stranger, $theirChild);

        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$theirChild->id}/journal")
            ->assertNotFound();
    }

    public function test_the_feed_merges_released_center_items_with_the_familys_own_entries(): void
    {
        $sent = $this->centerEntry(now()->subDay(), ReportStatus::Sent);
        $open = $this->centerEntry(now()->subDays(2), ReportStatus::Open);
        $sentPhoto = $this->centerMedia(MediaStatus::Sent);
        $draftPhoto = $this->centerMedia(MediaStatus::Draft);
        $mine = JournalEntry::factory()->create([
            'child_id' => $this->child->id,
            'guardian_id' => $this->guardian->id,
            'title' => 'First Plane Ride',
        ]);

        $response = $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/journal")
            ->assertOk();

        $keys = collect($response->json('data'))->map(fn ($item) => $item['kind'].':'.$item['id']);

        $this->assertTrue($keys->contains("entry:{$sent->id}"));
        $this->assertTrue($keys->contains("media:{$sentPhoto->id}"));
        $this->assertTrue($keys->contains("journal_entry:{$mine->id}"));
        $this->assertFalse($keys->contains("entry:{$open->id}"), 'an open report must not reach the family');
        $this->assertFalse($keys->contains("media:{$draftPhoto->id}"), 'draft media must not reach the family');

        // Newest day first.
        $dates = collect($response->json('data'))->pluck('date')->all();
        $this->assertSame($dates, collect($dates)->sortDesc()->values()->all());
    }

    public function test_the_feed_names_the_viewer_as_me(): void
    {
        JournalEntry::factory()->create([
            'child_id' => $this->child->id,
            'guardian_id' => $this->guardian->id,
        ]);

        $coParent = Guardian::factory()->registered()->create([
            'center_id' => $this->center->id,
            'first_name' => 'Wei',
            'last_name' => 'Chen',
        ]);
        $this->linkGuardianToChild($coParent, $this->child);
        JournalEntry::factory()->create([
            'child_id' => $this->child->id,
            'guardian_id' => $coParent->id,
            'entry_date' => now()->subDay()->toDateString(),
        ]);

        $names = collect($this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/journal")
            ->assertOk()
            ->json('data'))
            ->pluck('author.name');

        $this->assertTrue($names->contains('Me'));
        $this->assertTrue($names->contains('Wei Chen'));
    }

    public function test_the_delay_setting_hides_recent_photos_and_naps(): void
    {
        CenterSetting::create(['center_id' => $this->center->id, 'delayed_media_hours' => 4]);

        $recentPhoto = $this->centerMedia(MediaStatus::Sent, now()->subHour());
        $oldPhoto = $this->centerMedia(MediaStatus::Sent, now()->subHours(6));
        $recentNap = $this->centerEntry(now()->subHour(), ReportStatus::Sent, EntryType::Sleep);
        // A check-in is not something to sit on, delay or no delay.
        $recentCheckIn = $this->centerEntry(now()->subHour(), ReportStatus::Sent, EntryType::CheckIn);

        $keys = collect($this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/journal")
            ->assertOk()
            ->json('data'))
            ->map(fn ($item) => $item['kind'].':'.$item['id']);

        $this->assertFalse($keys->contains("media:{$recentPhoto->id}"));
        $this->assertTrue($keys->contains("media:{$oldPhoto->id}"));
        $this->assertFalse($keys->contains("entry:{$recentNap->id}"));
        $this->assertTrue($keys->contains("entry:{$recentCheckIn->id}"));
    }

    public function test_a_private_entry_is_hidden_from_a_crew_member_without_full_photo_access(): void
    {
        $author = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($author, $this->child);

        $private = JournalEntry::factory()->private()->create([
            'child_id' => $this->child->id,
            'guardian_id' => $author->id,
        ]);
        $shared = JournalEntry::factory()->create([
            'child_id' => $this->child->id,
            'guardian_id' => $author->id,
            'entry_date' => now()->subDay()->toDateString(),
        ]);

        $limited = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($limited, $this->child, ['has_full_photo_access' => false]);

        $keys = collect($this->actingAsGuardian($limited)
            ->getJson("/api/v1/children/{$this->child->id}/journal")
            ->assertOk()
            ->json('data'))
            ->map(fn ($item) => $item['kind'].':'.$item['id']);

        $this->assertFalse($keys->contains("journal_entry:{$private->id}"));
        $this->assertTrue($keys->contains("journal_entry:{$shared->id}"));

        $this->actingAsGuardian($limited)
            ->getJson("/api/v1/journal-entries/{$private->id}")
            ->assertNotFound();

        // …but the guardian with full photo access does see it.
        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/journal-entries/{$private->id}")
            ->assertOk();
    }

    public function test_creating_an_entry_stores_its_media_and_stays_invisible_to_the_center(): void
    {
        Storage::fake('public');

        $response = $this->actingAsGuardian($this->guardian)
            ->post("/api/v1/children/{$this->child->id}/journal-entries", [
                'title' => 'First Plane Ride',
                'description' => 'He slept the whole way.',
                'is_milestone' => true,
                'media' => [
                    UploadedFile::fake()->image('one.jpg'),
                    UploadedFile::fake()->image('two.jpg'),
                ],
            ])
            ->assertCreated();

        $response->assertJsonPath('data.title', 'First Plane Ride');
        $response->assertJsonPath('data.is_milestone', true);
        $response->assertJsonPath('data.entry_date', now()->toDateString());
        $response->assertJsonPath('data.author.name', 'Me');
        $response->assertJsonCount(2, 'data.media');

        foreach (JournalEntryMedia::all() as $media) {
            Storage::disk('public')->assertExists($media->file_path);
        }

        // The center's own tables never learn about it.
        $this->assertDatabaseCount('entries', 0);
        $this->assertDatabaseCount('media', 0);
        $this->assertDatabaseCount('daily_reports', 0);
    }

    public function test_creating_an_entry_requires_a_title_and_at_least_one_file(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->postJson("/api/v1/children/{$this->child->id}/journal-entries", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'media']);
    }

    public function test_only_the_author_may_edit_or_delete_an_entry(): void
    {
        Storage::fake('public');

        $entry = JournalEntry::factory()->create([
            'child_id' => $this->child->id,
            'guardian_id' => $this->guardian->id,
        ]);
        $file = JournalEntryMedia::factory()->create(['journal_entry_id' => $entry->id]);
        Storage::disk('public')->put($file->file_path, 'x');

        $coParent = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($coParent, $this->child);

        $payload = ['title' => 'Rewritten', 'entry_date' => now()->toDateString()];

        $this->actingAsGuardian($coParent)
            ->putJson("/api/v1/journal-entries/{$entry->id}", $payload)
            ->assertForbidden();
        $this->actingAsGuardian($coParent)
            ->deleteJson("/api/v1/journal-entries/{$entry->id}")
            ->assertForbidden();

        $this->actingAsGuardian($this->guardian)
            ->putJson("/api/v1/journal-entries/{$entry->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.title', 'Rewritten');

        $this->actingAsGuardian($this->guardian)
            ->deleteJson("/api/v1/journal-entries/{$entry->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('journal_entries', ['id' => $entry->id]);
        $this->assertDatabaseMissing('journal_entry_media', ['id' => $file->id]);
        Storage::disk('public')->assertMissing($file->file_path);
    }

    public function test_share_toggles_privacy_and_accepts_an_explicit_state(): void
    {
        $entry = JournalEntry::factory()->private()->create([
            'child_id' => $this->child->id,
            'guardian_id' => $this->guardian->id,
        ]);

        $this->actingAsGuardian($this->guardian)
            ->postJson("/api/v1/journal-entries/{$entry->id}/share")
            ->assertOk()
            ->assertJsonPath('data.is_private', false);

        $this->actingAsGuardian($this->guardian)
            ->postJson("/api/v1/journal-entries/{$entry->id}/share", ['shared' => false])
            ->assertOk()
            ->assertJsonPath('data.is_private', true);
    }

    public function test_an_account_admin_may_share_someone_elses_entry_but_a_plain_crew_member_may_not(): void
    {
        $entry = JournalEntry::factory()->private()->create([
            'child_id' => $this->child->id,
            'guardian_id' => $this->guardian->id,
        ]);

        $plain = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($plain, $this->child);

        $admin = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($admin, $this->child, ['is_account_admin' => true]);

        $this->actingAsGuardian($plain)
            ->postJson("/api/v1/journal-entries/{$entry->id}/share")
            ->assertForbidden();

        $this->actingAsGuardian($admin)
            ->postJson("/api/v1/journal-entries/{$entry->id}/share", ['shared' => true])
            ->assertOk()
            ->assertJsonPath('data.is_private', false);
    }

    public function test_the_entries_list_labels_the_viewers_own_rows(): void
    {
        $mine = JournalEntry::factory()->create([
            'child_id' => $this->child->id,
            'guardian_id' => $this->guardian->id,
            'entry_date' => '2026-03-09',
        ]);
        JournalEntryMedia::factory()->create(['journal_entry_id' => $mine->id]);

        $response = $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/entries")
            ->assertOk();

        $response->assertJsonPath('data.0.added_by', 'Me');
        $response->assertJsonPath('data.0.date', '3/9/26');
        $response->assertJsonPath('data.0.has_media', true);
    }

    private function centerEntry(\DateTimeInterface $at, ReportStatus $status, EntryType $type = EntryType::Activity): Entry
    {
        // One report per child per day — a second entry on the same date
        // rides on the report the first one created.
        $existing = DailyReport::where('child_id', $this->child->id)
            ->whereDate('report_date', $at->format('Y-m-d'))
            ->exists();

        if (! $existing) {
            DailyReport::factory()->create([
                'child_id' => $this->child->id,
                'report_date' => $at->format('Y-m-d'),
                'status' => $status,
            ]);
        }

        return Entry::factory()->create([
            'child_id' => $this->child->id,
            'classroom_id' => $this->classroom->id,
            'type' => $type,
            'occurred_at' => $at,
        ]);
    }

    private function centerMedia(MediaStatus $status, ?\DateTimeInterface $at = null): Media
    {
        $media = Media::factory()->create([
            'center_id' => $this->center->id,
            'classroom_id' => $this->classroom->id,
            'status' => $status,
            'occurred_at' => $at ?? now()->subDays(3),
            'sent_at' => $status === MediaStatus::Sent ? now() : null,
        ]);

        $media->children()->attach($this->child);

        return $media;
    }
}
