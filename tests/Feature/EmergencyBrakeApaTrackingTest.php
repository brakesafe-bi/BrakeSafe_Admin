<?php

namespace Tests\Feature;

use App\Models\AccidentProneArea;
use App\Models\BrakeEvent;
use App\Services\EmergencyBrakeApaTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmergencyBrakeApaTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_five_distinct_emergency_events_within_one_hundred_meters_create_an_apa_once(): void
    {
        $tracker = app(EmergencyBrakeApaTracker::class);
        $events = [];

        for ($index = 0; $index < 5; $index++) {
            $events[] = $this->createEmergencyEvent(
                11.2578671 + ($index * 0.0001),
                123.7142268,
                now()->addSeconds($index)
            );
        }

        foreach ($events as $event) {
            $tracker->process($event);
        }

        $this->assertDatabaseCount('accident_prone_areas', 1);
        $area = AccidentProneArea::firstOrFail();
        $this->assertSame(5, $area->emergency_hit_count);
        $this->assertTrue($area->is_auto_generated);

        $tracker->process($events[4]);
        $this->assertDatabaseCount('accident_prone_areas', 1);
        $this->assertSame(5, $area->fresh()->emergency_hit_count);
    }

    public function test_emergency_event_within_one_hundred_meters_increments_an_existing_area_hit_count(): void
    {
        $area = $this->createArea(11.2578671, 123.7142268);
        $event = $this->createEmergencyEvent(11.2580, 123.7142268);

        app(EmergencyBrakeApaTracker::class)->process($event);

        $this->assertSame(1, $area->fresh()->emergency_hit_count);
        $this->assertDatabaseCount('emergency_brake_apa_clusters', 0);
    }

    public function test_events_between_one_hundred_meters_and_five_kilometers_are_ignored(): void
    {
        $this->createArea(11.2578671, 123.7142268);
        $event = $this->createEmergencyEvent(11.267, 123.7142268);

        app(EmergencyBrakeApaTracker::class)->process($event);

        $this->assertDatabaseCount('emergency_brake_apa_clusters', 0);
        $this->assertDatabaseCount('accident_prone_areas', 1);
        $this->assertNotNull($event->fresh()->apa_processed_at);
    }

    public function test_non_emergency_brake_events_do_not_count_toward_an_apa(): void
    {
        $event = $this->createEmergencyEvent(11.2578671, 123.7142268, now(), 'hard');

        app(EmergencyBrakeApaTracker::class)->process($event);

        $this->assertDatabaseCount('emergency_brake_apa_clusters', 0);
        $this->assertDatabaseCount('accident_prone_areas', 0);
        $this->assertNull($event->fresh()->apa_processed_at);
    }

    public function test_imported_emergency_brake_events_create_an_apa_and_show_its_hit_count(): void
    {
        Storage::fake('local');
        $rows = [
            'Latitude,Longitude,Speed(km/h),Deceleration(g),Brake Level,Timestamp,Direction,Municipality,Barangay',
        ];
        for ($index = 0; $index < 5; $index++) {
            $rows[] = implode(',', [
                number_format(11.2578671 + ($index * 0.0001), 7, '.', ''),
                '123.7142268',
                '30',
                '0.75',
                'emergency',
                now()->addSeconds($index)->format('d/m/Y H:i:s'),
                'N',
                'Madridejos',
                'Tabagak',
            ]);
        }

        $this->actingAs(\App\Models\Admin::factory()->create())
            ->post(route('brake-events.import'), [
                'return_to' => 'brake-events',
                'brake_csv' => UploadedFile::fake()->createWithContent(
                    'emergency-brakes.csv',
                    implode("\n", $rows)
                ),
            ])
            ->assertRedirect(route('brake-events.index'))
            ->assertSessionHas('status', 'Imported 5 brake events.');

        $this->assertDatabaseCount('accident_prone_areas', 1);
        $this->assertDatabaseHas('accident_prone_areas', [
            'emergency_hit_count' => 5,
            'is_auto_generated' => true,
        ]);

        $this->actingAs(\App\Models\Admin::factory()->create())
            ->get(route('analytics.apa'))
            ->assertOk()
            ->assertSee('"emergency_hit_count":5', false)
            ->assertSee('"is_auto_generated":true', false);
    }

    public function test_api_emergency_events_create_an_apa_after_five_hits(): void
    {
        for ($index = 0; $index < 5; $index++) {
            $this->postJson('/api/brake-events', [
                'vehicle_id' => 'test-vehicle',
                'device_id' => 'test-device',
                'latitude' => 11.2578671 + ($index * 0.0001),
                'longitude' => 123.7142268,
                'speed_kmph' => 30,
                'deceleration_g' => 0.75,
                'brake_level' => 'emergency',
                'direction' => 'N',
                'timestamp' => now()->addSeconds($index)->format('Y-m-d\TH:i:s\Z'),
            ])->assertCreated();
        }

        $this->assertDatabaseCount('brake_events', 5);
        $this->assertDatabaseHas('accident_prone_areas', [
            'emergency_hit_count' => 5,
            'is_auto_generated' => true,
        ]);
    }

    private function createEmergencyEvent(
        float $latitude,
        float $longitude,
        mixed $timestamp = null,
        string $classification = 'emergency'
    ): BrakeEvent {
        $now = now();
        $id = DB::table('brake_events')->insertGetId([
            'vehicle_id' => 'test-vehicle',
            'device_id' => 'test-device',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'speed_kmh' => 30,
            'deceleration_force' => 0.75,
            'brake_classification' => $classification,
            'municipality' => 'Madridejos',
            'barangay' => 'Tabagak',
            'direction' => 'N',
            'timestamp' => $timestamp ?? $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return BrakeEvent::findOrFail($id);
    }

    private function createArea(float $latitude, float $longitude): AccidentProneArea
    {
        return AccidentProneArea::create([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'barangay' => 'Tabagak',
            'municipality' => 'Madridejos',
            'province' => 'Cebu',
            'country' => 'Philippines',
            'place' => 'Tabagak, Madridejos, Cebu, Philippines',
            'reason' => 'Manual test area',
        ]);
    }
}
