<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BrakeEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BrakeEventsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_brake_events_page_renders_loaded_dataset_and_event_rows(): void
    {
        Storage::fake('local');
        $event = BrakeEvent::create($this->eventAttributes());
        Storage::disk('local')->put('settings/dashboard-dataset.json', json_encode([
            'file_name' => 'brake_events.csv',
            'event_ids' => [$event->id],
        ]));

        $this->actingAs(Admin::factory()->create())
            ->get('/brake-events')
            ->assertOk()
            ->assertSee('Loaded file: brake_events.csv')
            ->assertSee('Ready')
            ->assertSee('1 mapped events')
            ->assertSee('14/10/2026 15:07:58')
            ->assertSee('35.02')
            ->assertSee('0.25')
            ->assertSee('Export CSV')
            ->assertSee('Clear data')
            ->assertSee('Imported rows');
    }

    public function test_brake_events_map_cannot_zoom_out_past_the_minimum_level(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->get('/brake-events')
            ->assertOk()
            ->assertSee('minZoom: 10', false)
            ->assertSee('map.getZoom() > map.getMinZoom()', false)
            ->assertSee('mapZoomOutButton.disabled', false);
    }

    public function test_export_and_clear_only_affect_the_selected_dataset_records(): void
    {
        Storage::fake('local');
        $datasetEvent = BrakeEvent::create($this->eventAttributes());
        $unrelatedEvent = BrakeEvent::create($this->eventAttributes([
            'latitude' => 11.2,
            'longitude' => 123.7,
        ]));
        Storage::disk('local')->put('settings/dashboard-dataset.json', json_encode([
            'file_name' => 'brake_events.csv',
            'event_ids' => [$datasetEvent->id],
        ]));
        Storage::disk('local')->put('settings/analytics-dataset.json', json_encode([
            'file_name' => 'brake_events.csv',
            'event_ids' => [$datasetEvent->id],
        ]));

        $this->actingAs(Admin::factory()->create())
            ->get('/brake-events/export?municipality=Madridejos')
            ->assertOk()
            ->assertDownload('brake_events-export.csv');

        $this->delete('/brake-events/dataset')->assertRedirect('/brake-events');

        $this->assertDatabaseMissing('brake_events', ['id' => $datasetEvent->id]);
        $this->assertDatabaseHas('brake_events', ['id' => $unrelatedEvent->id]);
        $this->assertFalse(Storage::disk('local')->exists('settings/dashboard-dataset.json'));
        $this->assertFalse(Storage::disk('local')->exists('settings/analytics-dataset.json'));
    }

    public function test_export_matches_the_requested_brake_event_csv_format(): void
    {
        Storage::fake('local');
        $now = now();
        $eventId = DB::table('brake_events')->insertGetId([
            'vehicle_id' => 'test-vehicle',
            'device_id' => 'test-device',
            'latitude' => 11.295201,
            'longitude' => 123.72835,
            'speed_kmh' => 35.02,
            'deceleration_force' => 0.25,
            'brake_classification' => 'soft',
            'municipality' => 'Madridejos',
            'barangay' => 'Talangnan',
            'direction' => 'N',
            'timestamp' => '2026-10-14 15:07:58',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        Storage::disk('local')->put('settings/dashboard-dataset.json', json_encode([
            'file_name' => 'brake_events.csv',
            'event_ids' => [$eventId],
        ]));

        $response = $this->actingAs(Admin::factory()->create())
            ->get('/brake-events/export');

        $response->assertOk()->assertDownload('brake_events-export.csv');
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $response->streamedContent());
        rewind($stream);

        $this->assertSame([
            'Latitude',
            'Longitude',
            'Speed(km/h)',
            'Deceleration(g)',
            'Brake Level',
            'Timestamp',
            'Direction',
            'Municipality',
            'Barangay',
        ], fgetcsv($stream));
        $this->assertSame([
            '11.295201',
            '123.72835',
            '35.02',
            '0.25',
            'soft',
            '14/10/2026 15:07',
            'N',
            'Madridejos',
            'Talangnan',
        ], fgetcsv($stream));
        fclose($stream);
    }

    public function test_import_accepts_only_the_two_supported_brake_event_headers(): void
    {
        Storage::fake('local');
        $admin = Admin::factory()->create();
        $acceptedHeaders = [
            "Latitude,Longitude,Speed(km/h),Deceleration(g),Brake Level,Timestamp,Direction\n",
            "Latitude,Longitude,Speed(km/h),Deceleration(g),Brake Level,Timestamp,Direction,Municipality,Barangay\n",
        ];

        foreach ($acceptedHeaders as $headers) {
            $this->actingAs($admin)
                ->post(route('brake-events.import'), [
                    'return_to' => 'brake-events',
                    'brake_csv' => UploadedFile::fake()->createWithContent('brake-events.csv', $headers),
                ])
                ->assertRedirect(route('brake-events.index'))
                ->assertSessionHas('status', 'Imported 0 brake events.');
        }

        $this->actingAs($admin)
            ->post(route('brake-events.import'), [
                'return_to' => 'brake-events',
                'brake_csv' => UploadedFile::fake()->createWithContent(
                    'wrong-format.csv',
                    "Latitude,Longitude,Timestamp\n11.295201,123.72835,14/10/2026 15:07\n"
                ),
            ])
            ->assertRedirect(route('brake-events.index'))
            ->assertSessionHas('import_error');

        $this->get(route('brake-events.index'))
            ->assertOk()
            ->assertSee('Import declined')
            ->assertSee('Use one of these column formats');
    }

    public function test_import_declines_brake_events_that_are_already_present_or_repeated_in_the_file(): void
    {
        Storage::fake('local');
        $admin = Admin::factory()->create();
        $now = now();
        DB::table('brake_events')->insert([
            'vehicle_id' => 'existing-vehicle',
            'device_id' => 'existing-device',
            'latitude' => 11.295201,
            'longitude' => 123.72835,
            'speed_kmh' => 35.02,
            'deceleration_force' => 0.25,
            'brake_classification' => 'soft',
            'municipality' => 'Madridejos',
            'barangay' => 'Talangnan',
            'direction' => 'N',
            'timestamp' => '2026-10-14 15:07:00',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $duplicateRow = "11.295201,123.72835,35.02,0.25,soft,14/10/2026 15:07,N,Madridejos,Talangnan";
        $csvHeader = "Latitude,Longitude,Speed(km/h),Deceleration(g),Brake Level,Timestamp,Direction,Municipality,Barangay\n";

        $existingResponse = $this->actingAs($admin)
            ->post(route('brake-events.import'), [
                'return_to' => 'brake-events',
                'brake_csv' => UploadedFile::fake()->createWithContent(
                    'duplicate-existing.csv',
                    $csvHeader . $duplicateRow . "\n"
                ),
            ]);
        $this->assertSame(
            'Import declined. One or more brake events in this file are already in the database.',
            $existingResponse->getSession()->get('import_error')
        );
        $existingResponse->assertRedirect(route('brake-events.index'));

        $this->actingAs($admin)
            ->post(route('brake-events.import'), [
                'return_to' => 'brake-events',
                'brake_csv' => UploadedFile::fake()->createWithContent(
                    'duplicate-in-file.csv',
                    $csvHeader . $duplicateRow . "\n" . $duplicateRow . "\n"
                ),
            ])
            ->assertRedirect(route('brake-events.index'))
            ->assertSessionHas('import_error', 'Import declined. The file contains duplicate brake-event data.');

        $this->assertDatabaseCount('brake_events', 1);
        $this->assertFalse(Storage::disk('local')->exists('settings/dashboard-dataset.json'));
    }

    public function test_brake_event_filters_are_restored_from_the_url(): void
    {
        Storage::fake('local');
        $event = BrakeEvent::create($this->eventAttributes(['barangay' => 'Bunakan']));
        Storage::disk('local')->put('settings/dashboard-dataset.json', json_encode([
            'file_name' => 'brake_events.csv',
            'event_ids' => [$event->id],
        ]));

        $this->actingAs(Admin::factory()->create())
            ->get('/brake-events?municipality=Madridejos&barangay=Bunakan')
            ->assertOk()
            ->assertSee('const params = new URLSearchParams(window.location.search);', false)
            ->assertSee("refreshBarangays(params.get('barangay') || 'All barangays')", false);
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
            'timestamp' => '2026-10-14 15:07:58',
        ], $overrides);
    }
}
