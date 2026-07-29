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
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsApiTest extends TestCase
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

    public function test_reports_require_a_token(): void
    {
        $this->getJson("/api/v1/children/{$this->child->id}/reports")->assertUnauthorized();
    }

    public function test_another_guardians_child_has_no_report(): void
    {
        $stranger = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $theirChild = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($stranger, $theirChild);

        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$theirChild->id}/reports")
            ->assertNotFound();
    }

    public function test_a_day_with_no_report_says_none(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/reports?date=2026-07-01")
            ->assertOk()
            ->assertJsonPath('status', 'none')
            ->assertJsonPath('date', '2026-07-01')
            ->assertJsonPath('sections', [])
            ->assertJsonPath('media', []);
    }

    public function test_an_open_report_exposes_no_entry_data(): void
    {
        $date = now()->toDateString();
        DailyReport::factory()->create([
            'child_id' => $this->child->id,
            'report_date' => $date,
            'status' => ReportStatus::Open,
        ]);
        $this->entry(EntryType::Food, now()->subHours(3), ['meal' => 'Lunch', 'amount' => 'Most']);

        $response = $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/reports?date={$date}")
            ->assertOk()
            ->assertJsonPath('status', 'not_finalized')
            ->assertJsonPath('sections', [])
            ->assertJsonPath('media', []);

        $this->assertStringNotContainsString('Lunch', $response->getContent());
    }

    public function test_a_sent_report_groups_entries_in_section_order(): void
    {
        $date = now()->toDateString();
        DailyReport::factory()->create([
            'child_id' => $this->child->id,
            'report_date' => $date,
            'status' => ReportStatus::Sent,
            'sent_at' => now(),
        ]);

        // Created out of order on purpose.
        $this->entry(EntryType::Activity, now()->setTime(10, 0), ['notes' => 'Painting']);
        $this->entry(EntryType::CheckIn, now()->setTime(8, 55));
        $this->entry(EntryType::Food, now()->setTime(11, 30), ['meal' => 'Lunch', 'amount' => 'Most']);
        // Staff bookkeeping never reaches the family.
        $this->entry(EntryType::NameToFace, now()->setTime(9, 0));

        $response = $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/reports?date={$date}")
            ->assertOk()
            ->assertJsonPath('status', 'sent');

        $this->assertNotNull($response->json('sent_at'));
        $this->assertSame(
            [EntryType::CheckIn->value, EntryType::Food->value, EntryType::Activity->value],
            array_column($response->json('sections'), 'type'),
        );

        $food = $response->json('sections.1');
        $this->assertSame('Food', $food['label']);
        $this->assertSame('11:30', $food['items'][0]['time']);
        $this->assertSame('Lunch · ate Most', $food['items'][0]['summary']);
        $this->assertSame('Most', $food['items'][0]['qty']);
    }

    public function test_a_sent_report_includes_that_days_sent_media(): void
    {
        $date = now()->toDateString();
        DailyReport::factory()->create([
            'child_id' => $this->child->id,
            'report_date' => $date,
            'status' => ReportStatus::Sent,
            'sent_at' => now(),
        ]);

        $sent = $this->media(MediaStatus::Sent, now()->subHours(2));
        $draft = $this->media(MediaStatus::Draft, now()->subHours(2));
        $otherDay = $this->media(MediaStatus::Sent, now()->subDays(2));

        $ids = array_column($this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/reports?date={$date}")
            ->assertOk()
            ->json('media'), 'id');

        $this->assertContains($sent->id, $ids);
        $this->assertNotContains($draft->id, $ids);
        $this->assertNotContains($otherDay->id, $ids);
    }

    public function test_the_delay_setting_hides_recent_naps_meals_and_media(): void
    {
        CenterSetting::create(['center_id' => $this->center->id, 'delayed_media_hours' => 4]);

        $date = now()->toDateString();
        DailyReport::factory()->create([
            'child_id' => $this->child->id,
            'report_date' => $date,
            'status' => ReportStatus::Sent,
            'sent_at' => now(),
        ]);

        $this->entry(EntryType::Sleep, now()->subHour(), ['start_time' => '12:30']);
        $this->entry(EntryType::Food, now()->subHours(6), ['meal' => 'Breakfast', 'amount' => 'All']);
        $this->entry(EntryType::CheckIn, now()->subHour());
        $recentPhoto = $this->media(MediaStatus::Sent, now()->subHour());
        $oldPhoto = $this->media(MediaStatus::Sent, now()->subHours(6));

        $response = $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/reports?date={$date}")
            ->assertOk();

        $types = array_column($response->json('sections'), 'type');
        $this->assertContains(EntryType::CheckIn->value, $types, 'a check-in is never delayed');
        $this->assertContains(EntryType::Food->value, $types, 'an old meal is outside the window');
        $this->assertNotContains(EntryType::Sleep->value, $types, 'a recent nap must wait out the delay');

        $ids = array_column($response->json('media'), 'id');
        $this->assertNotContains($recentPhoto->id, $ids);
        $this->assertContains($oldPhoto->id, $ids);
    }

    private function entry(EntryType $type, \DateTimeInterface $at, array $payload = []): Entry
    {
        return Entry::factory()->create([
            'child_id' => $this->child->id,
            'classroom_id' => $this->classroom->id,
            'type' => $type,
            'occurred_at' => $at,
            'payload' => $payload,
        ]);
    }

    private function media(MediaStatus $status, \DateTimeInterface $at): Media
    {
        $media = Media::factory()->create([
            'center_id' => $this->center->id,
            'classroom_id' => $this->classroom->id,
            'status' => $status,
            'occurred_at' => $at,
            'sent_at' => $status === MediaStatus::Sent ? now() : null,
        ]);

        $media->children()->attach($this->child);

        return $media;
    }
}
