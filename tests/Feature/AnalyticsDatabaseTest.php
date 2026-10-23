<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BrakeEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_uses_database_events_without_import_metadata(): void
    {
        BrakeEvent::create($this->eventAttributes());
        BrakeEvent::create($this->eventAttributes([
            'latitude' => 91,
            'speed_kmh' => 42,
            'deceleration_force' => 0.8,
            'brake_classification' => 'hard',
        ]));

        $this->actingAs(Admin::factory()->create())
            ->get('/analytics')
            ->assertOk()
            ->assertSee('1 mapped events')
            ->assertSee('2 events')
            ->assertSee('All municipalities')
            ->assertSee('<input id="analytics-event-date" type="date"', false)
            ->assertSee('<option value="all" selected>All dates</option>', false)
            ->assertSee('<option value="2026-10-14">10/14/2026</option>', false)
            ->assertSee('Top 1 barangays by brake severity, vertical stacked bar chart')
            ->assertSee('Kodia')
            ->assertSee('style="--bar-height: 144.0000px"', false)
            ->assertSee('data-severity="Soft"', false)
            ->assertSee('data-event-count="1"', false)
            ->assertSee('style="height: 144.0000px; background-color: #22c55e"', false)
            ->assertSee('Madridejos, Kodia: Soft, 1 brake events')
            ->assertSee('id="severity-soft" class="mt-2 text-2xl font-semibold text-white">1', false)
            ->assertSee('#analytics-page [aria-labelledby="severity-summary-title"] dl dt', false)
            ->assertSee('font-size: 1.875rem;', false)
            ->assertSee('id="check-invalid-locations" class="text-sm font-medium text-slate-200">1', false)
            ->assertSee('42.00 km/h')
            ->assertSee('0.80 g');
    }

    public function test_analytics_shows_live_update_time_in_shared_sidebar(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->get('/analytics')
            ->assertOk()
            ->assertSee('id="live-update-indicator"', false)
            ->assertSee('id="profile-sidebar"', false)
            ->assertSee('Live updates ·', false)
            ->assertSee('formatUpdateTime()', false)
            ->assertDontSee('analytics-live-update-indicator', false);
    }

    public function test_desktop_severity_totals_have_room_for_larger_values(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->get('/analytics')
            ->assertOk()
            ->assertSee('id="severity-summary-grid"', false)
            ->assertSee('#severity-summary-grid', false)
            ->assertSee('grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);', false)
            ->assertSee('font-size: clamp(2rem, 2.5vw, 2.25rem);', false)
            ->assertSee('id="severity-soft"', false)
            ->assertSee('id="severity-moderate"', false)
            ->assertSee('id="severity-hard"', false)
            ->assertSee('id="severity-emergency"', false);
    }

    public function test_analytics_filter_changes_refresh_severity_summary_without_search(): void
    {
        $view = file_get_contents(resource_path('views/analytics/index.blade.php'));
        $this->assertIsString($view);
        $view = str_replace("\r\n", "\n", $view);

        foreach ([
            "municipalityFilter.addEventListener('change', () => {\n            refreshBarangays();\n            syncFiltersToUrl();\n            renderEvents();\n        });",
            "barangayFilter.addEventListener('change', () => {\n            syncFiltersToUrl();\n            renderEvents();\n        });",
            "availableDateFilter.addEventListener('change', () => {\n            if (availableDateFilter.value === 'all') {\n                dateFilter.value = '';\n            } else if (availableDateFilter.value) {\n                dateFilter.value = availableDateFilter.value;\n            }\n            syncFiltersToUrl();\n            renderEvents();\n        });",
            "dateFilter.addEventListener('change', () => {\n            const matchingDate = [...availableDateFilter.options]\n                .some((option) => option.value === dateFilter.value)\n                ? dateFilter.value\n                : 'all';\n            availableDateFilter.value = matchingDate;\n            syncFiltersToUrl();\n            renderEvents();\n        });",
        ] as $handler) {
            $this->assertStringContainsString($handler, $view);
        }
    }

    public function test_barangay_chart_keeps_small_nonzero_severity_segments_visible(): void
    {
        $view = file_get_contents(resource_path('views/analytics/index.blade.php'));
        $this->assertIsString($view);
        $view = str_replace("\r\n", "\n", $view);

        $this->assertStringContainsString('$minimumSeveritySegmentHeight = 6;', $view);
        $this->assertStringContainsString('max($minimumSeveritySegmentHeight, ($count / $maxBarangayTotal) * $maximumSeverityBarHeight)', $view);
        $this->assertStringContainsString('Math.max(minimumSeveritySegmentHeight, value / maxTotal * maximumSeverityBarHeight)', $view);
        $this->assertStringContainsString('const barHeight = Object.values(segmentHeights).reduce((total, height) => total + height, 0);', $view);
    }

    public function test_all_leaflet_maps_use_zoom_level_16_as_the_maximum(): void
    {
        foreach ([
            'dashboard/index.blade.php',
            'incidents/index.blade.php',
            'analytics/index.blade.php',
            'analytics/apa.blade.php',
        ] as $view) {
            $contents = file_get_contents(resource_path('views/'.$view));
            $this->assertIsString($contents);
            $this->assertStringContainsString('maxZoom: 16', $contents, $view);
            $this->assertStringNotContainsString('maxZoom: 19', $contents, $view);
        }
    }

    public function test_all_maps_switch_between_dark_and_light_tiles_with_the_admin_theme(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));
        $this->assertIsString($layout);
        $this->assertStringContainsString('window.BrakeSafeMapTheme =', $layout);
        $this->assertStringContainsString("'streets-v2-dark'", $layout);
        $this->assertStringContainsString("'streets-v2'", $layout);
        $this->assertStringContainsString("isLight ? '#87ceeb' : '#000000'", $layout);
        $this->assertStringContainsString("mixBlendMode: 'multiply'", $layout);
        $this->assertStringContainsString("tint.style.opacity = isLight ? '0.85' : '0'", $layout);
        $this->assertStringContainsString('brakesafe:theme-change', $layout);

        foreach ([
            'dashboard/index.blade.php',
            'incidents/index.blade.php',
            'analytics/index.blade.php',
            'analytics/apa.blade.php',
        ] as $view) {
            $contents = file_get_contents(resource_path('views/'.$view));
            $this->assertIsString($contents);
            $this->assertStringContainsString('BrakeSafeMapTheme.tileUrl', $contents, $view);
            $this->assertStringContainsString('BrakeSafeMapTheme.watch', $contents, $view);
        }
    }

    public function test_event_day_filter_lists_only_days_available_in_database(): void
    {
        BrakeEvent::create($this->eventAttributes());
        BrakeEvent::create($this->eventAttributes([
            'latitude' => 11.296,
            'longitude' => 123.729,
            'timestamp' => '2026-10-12 09:00:00',
        ]));

        $response = $this->actingAs(Admin::factory()->create())->get('/analytics');

        $response->assertOk()
            ->assertSee('<input id="analytics-event-date" type="date"', false)
            ->assertSee('<option value="all" selected>All dates</option>', false)
            ->assertSee('<option value="2026-10-14">10/14/2026</option>', false)
            ->assertSee('<option value="2026-10-12">10/12/2026</option>', false)
            ->assertDontSee('<option value="2026-10-13">2026-10-13</option>', false);

        $this->assertLessThan(
            strpos($response->getContent(), '<option value="2026-10-12">'),
            strpos($response->getContent(), '<option value="2026-10-14">')
        );
    }

    public function test_barangay_chart_count_matches_available_barangays_and_is_capped_at_ten(): void
    {
        foreach (range(1, 11) as $index) {
            BrakeEvent::create($this->eventAttributes([
                'latitude' => 11.2 + ($index / 10000),
                'longitude' => 123.7 + ($index / 10000),
                'barangay' => "Barangay {$index}",
            ]));
        }

        $response = $this->actingAs(Admin::factory()->create())->get('/analytics');

        $response->assertOk()
            ->assertSee('Top 10 barangays by brake severity')
            ->assertSee('Top 10 barangays by brake severity, vertical stacked bar chart')
            ->assertSee('10 barangays');

        $this->assertSame(10, substr_count($response->getContent(), 'class="top-barangay-column'));
    }

    private function eventAttributes(array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => 'test-vehicle',
            'latitude' => 11.295201,
            'longitude' => 123.728348,
            'speed_kmh' => 35.02,
            'deceleration_force' => 0.25,
            'brake_classification' => 'soft',
            'municipality' => 'Madridejos',
            'barangay' => 'Kodia',
            'timestamp' => '2026-10-14 15:07:58',
        ], $overrides);
    }
}
