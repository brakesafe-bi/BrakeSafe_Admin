<?php

namespace Tests\Feature;

use App\Models\AccidentProneArea;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardProximityAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_severity_bar_segments_show_their_count_on_hover(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('title="Bantayan: 0 soft events"', false)
            ->assertSee('title="Madridejos: 0 moderate events"', false)
            ->assertSee('title="Santa Fe: 0 emergency events"', false);
    }

    public function test_dashboard_displays_municipality_contribution_pie_chart(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('id="municipality-contribution-chart"', false)
            ->assertSee("type: 'pie'", false)
            ->assertSee('Municipality contribution pie chart.')
            ->assertSee('count.toLocaleString()} events (${percentage.toFixed(1)}%)', false)
            ->assertSeeInOrder(['aria-labelledby="severity-title"', 'aria-labelledby="dataset-title"', 'aria-labelledby="contribution-title"'], false)
            ->assertSee('Current dataset');
    }

    public function test_dashboard_map_cannot_zoom_out_past_the_minimum_level(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('minZoom: 10', false)
            ->assertSee('map.getZoom() > map.getMinZoom()', false)
            ->assertSee('mapZoomOutButton.disabled', false);
    }

    public function test_dashboard_shows_a_500_meter_gps_radius_and_places_notifications_in_the_header(): void
    {
        $response = $this->actingAs(Admin::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('500 m radius')
            ->assertSee('radius: 500', false)
            ->assertSee('id="nearby-notifications-toggle"', false);

        $content = $response->getContent();

        $this->assertSame(1, substr_count($content, 'id="nearby-notifications-toggle"'));
        $this->assertMatchesRegularExpression(
            '/<div id="admin-user-badge"[^>]*>\s*<button id="nearby-notifications-toggle"/s',
            $content
        );
    }

    public function test_dashboard_supplies_all_brake_events_and_accident_prone_locations_for_nearby_alerts(): void
    {
        $now = now();
        $hardEventId = DB::table('brake_events')->insertGetId([
            'vehicle_id' => 'vehicle-hard',
            'device_id' => 'device-hard',
            'latitude' => 11.2,
            'longitude' => 123.7,
            'speed_kmh' => 40,
            'deceleration_force' => 2,
            'brake_classification' => 'hard',
            'municipality' => 'Bantayan',
            'barangay' => 'Poblacion',
            'timestamp' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $emergencyEventId = DB::table('brake_events')->insertGetId([
            'vehicle_id' => 'vehicle-emergency',
            'device_id' => 'device-emergency',
            'latitude' => 11.21,
            'longitude' => 123.71,
            'speed_kmh' => 50,
            'deceleration_force' => 3,
            'brake_classification' => 'emergency',
            'municipality' => 'Madridejos',
            'barangay' => 'Tabagak',
            'timestamp' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $softEventId = DB::table('brake_events')->insertGetId([
            'vehicle_id' => 'vehicle-soft',
            'device_id' => 'device-soft',
            'latitude' => 11.22,
            'longitude' => 123.72,
            'speed_kmh' => 30,
            'deceleration_force' => 1,
            'brake_classification' => 'soft',
            'municipality' => 'Santa Fe',
            'barangay' => 'Poblacion',
            'timestamp' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $moderateEventId = DB::table('brake_events')->insertGetId([
            'vehicle_id' => 'vehicle-moderate',
            'device_id' => 'device-moderate',
            'latitude' => 11.225,
            'longitude' => 123.725,
            'speed_kmh' => 35,
            'deceleration_force' => 1.5,
            'brake_classification' => 'medium',
            'municipality' => 'Bantayan',
            'barangay' => 'Poblacion',
            'timestamp' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $area = AccidentProneArea::create([
            'latitude' => 11.23,
            'longitude' => 123.73,
            'barangay' => 'Poblacion',
            'municipality' => 'Bantayan',
            'province' => 'Cebu',
            'country' => 'Philippines',
            'place' => 'Poblacion, Bantayan, Cebu, Philippines',
            'reason' => 'Accident Prone Area',
        ]);

        $admin = Admin::factory()->create();
        $response = $this->actingAs($admin)->get('/');
        $response->assertOk();
        $expectedTargetIds = [
            "brake-event-{$hardEventId}",
            "brake-event-{$emergencyEventId}",
            "brake-event-{$softEventId}",
            "brake-event-{$moderateEventId}",
            "apa-{$area->id}",
        ];
        $this->assertEqualsCanonicalizing(
            $expectedTargetIds,
            $response->viewData('proximityTargets')->pluck('id')->all()
        );

        $targetsResponse = $this->actingAs($admin)
            ->getJson('/nearby-alert-targets')
            ->assertOk()
            ->assertJsonCount(5, 'targets')
            ->assertJsonFragment([
                'id' => "apa-{$area->id}",
                'place' => 'Poblacion, Bantayan',
            ]);

        $this->assertEqualsCanonicalizing(
            $expectedTargetIds,
            collect($targetsResponse->json('targets'))->pluck('id')->all()
        );
        $this->assertSame('Moderate', collect($targetsResponse->json('targets'))
            ->firstWhere('id', "brake-event-{$moderateEventId}")['type']);
    }

    public function test_nearby_alert_targets_require_admin_authentication(): void
    {
        $this->getJson('/nearby-alert-targets')->assertUnauthorized();
    }
}
