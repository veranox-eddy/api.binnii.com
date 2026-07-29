<?php

namespace Tests\Feature\Api;

use App\Models\CalendarEvent;
use App\Models\Center;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\MenusCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarApiTest extends TestCase
{
    use RefreshDatabase;

    private Center $center;

    private Guardian $guardian;

    private Child $child;

    private MenusCalendar $visible;

    protected function setUp(): void
    {
        parent::setUp();

        $this->center = Center::factory()->create(['timezone' => 'UTC']);
        $this->guardian = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->child = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($this->guardian, $this->child);

        $this->visible = MenusCalendar::factory()->create([
            'center_id' => $this->center->id,
            'parent_visible' => true,
        ]);
    }

    public function test_the_calendar_requires_a_token(): void
    {
        $this->getJson("/api/v1/children/{$this->child->id}/calendar")->assertUnauthorized();
    }

    public function test_another_guardians_child_has_no_calendar(): void
    {
        $stranger = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $theirChild = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($stranger, $theirChild);

        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$theirChild->id}/calendar")
            ->assertNotFound();
    }

    public function test_a_month_returns_visible_events_with_the_child_context(): void
    {
        $classroom = Classroom::factory()->create([
            'center_id' => $this->center->id,
            'name' => 'Infant and Toddler Room',
        ]);
        Enrollment::factory()->create(['child_id' => $this->child->id, 'classroom_id' => $classroom->id]);

        $inMonth = CalendarEvent::create([
            'menus_calendar_id' => $this->visible->id,
            'event_date' => '2026-07-15',
            'title' => 'Summer Picnic',
            'description' => 'Bring a hat.',
        ]);
        CalendarEvent::create([
            'menus_calendar_id' => $this->visible->id,
            'event_date' => '2026-09-15',
            'title' => 'Too far out',
        ]);

        $response = $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/calendar?month=2026-07")
            ->assertOk()
            ->assertJsonPath('range.month', '2026-07')
            ->assertJsonPath('child.id', $this->child->id)
            ->assertJsonPath('child.classroom', 'Infant and Toddler Room')
            ->assertJsonCount(1, 'events');

        $event = $response->json('events.0');
        $this->assertSame('2026-07-15', $event['date']);
        $this->assertSame('Summer Picnic', $event['title']);
        $this->assertSame('Bring a hat.', $event['description']);
        $this->assertFalse($event['has_attachment']);
    }

    public function test_the_month_grid_includes_leading_and_trailing_days(): void
    {
        // July 2026 starts on a Wednesday; June 28 (Sunday) opens its grid.
        CalendarEvent::create([
            'menus_calendar_id' => $this->visible->id,
            'event_date' => '2026-06-28',
            'title' => 'Grid leader',
        ]);
        // August 1 (Saturday) closes it.
        CalendarEvent::create([
            'menus_calendar_id' => $this->visible->id,
            'event_date' => '2026-08-01',
            'title' => 'Grid trailer',
        ]);
        CalendarEvent::create([
            'menus_calendar_id' => $this->visible->id,
            'event_date' => '2026-06-27',
            'title' => 'Before the grid',
        ]);

        $titles = array_column($this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/calendar?month=2026-07")
            ->assertOk()
            ->json('events'), 'title');

        $this->assertContains('Grid leader', $titles);
        $this->assertContains('Grid trailer', $titles);
        $this->assertNotContains('Before the grid', $titles);
    }

    public function test_a_week_returns_only_that_weeks_events(): void
    {
        CalendarEvent::create([
            'menus_calendar_id' => $this->visible->id,
            'event_date' => '2026-07-21',
            'title' => 'In week',
        ]);
        CalendarEvent::create([
            'menus_calendar_id' => $this->visible->id,
            'event_date' => '2026-07-28',
            'title' => 'Next week',
        ]);

        $titles = array_column($this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/calendar?week=2026-07-20")
            ->assertOk()
            ->assertJsonPath('range.week', '2026-07-20')
            ->json('events'), 'title');

        $this->assertSame(['In week'], $titles);
    }

    public function test_hidden_calendars_and_other_centers_never_leak(): void
    {
        $hidden = MenusCalendar::factory()->create([
            'center_id' => $this->center->id,
            'parent_visible' => false,
        ]);
        CalendarEvent::create([
            'menus_calendar_id' => $hidden->id,
            'event_date' => '2026-07-15',
            'title' => 'Staff only',
        ]);

        $otherCenter = MenusCalendar::factory()->create(['parent_visible' => true]);
        CalendarEvent::create([
            'menus_calendar_id' => $otherCenter->id,
            'event_date' => '2026-07-15',
            'title' => 'Different center',
        ]);

        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/calendar?month=2026-07")
            ->assertOk()
            ->assertJsonCount(0, 'events');
    }

    public function test_an_empty_month_returns_an_empty_list(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/calendar?month=2026-02")
            ->assertOk()
            ->assertJsonPath('events', []);
    }
}
