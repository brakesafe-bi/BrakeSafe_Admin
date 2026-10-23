<?php

namespace Tests\Feature;

use App\Models\AccidentProneArea;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccidentProneAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_location_is_saved_with_polygon_place_and_default_region(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->postJson('/apa/locations', [
                'latitude' => 11.2578671,
                'longitude' => 123.7142268,
                'reason' => 'Accident Prone Area',
            ])
            ->assertCreated()
            ->assertJsonPath('area.barangay', 'Tabagak')
            ->assertJsonPath('area.municipality', 'Madridejos')
            ->assertJsonPath('area.province', 'Cebu')
            ->assertJsonPath('area.country', 'Philippines')
            ->assertJsonPath('area.place', 'Tabagak, Madridejos, Cebu, Philippines')
            ->assertJsonPath('area.reason', 'Accident Prone Area');

        $this->assertDatabaseHas('accident_prone_areas', [
            'place' => 'Tabagak, Madridejos, Cebu, Philippines',
            'reason' => 'Accident Prone Area',
        ]);
    }

    public function test_map_location_outside_the_three_supported_municipalities_is_rejected(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->postJson('/apa/locations', [
                'latitude' => 10.3157,
                'longitude' => 123.8854,
                'reason' => 'Accident Prone Area',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('latitude');

        $this->assertSame(0, AccidentProneArea::count());
    }

    public function test_reason_is_sanitized_when_a_location_is_created(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->postJson('/apa/locations', [
                'latitude' => 11.2578671,
                'longitude' => 123.7142268,
                'reason' => "  <b>Road hazard</b>\n  at night  ",
            ])
            ->assertCreated()
            ->assertJsonPath('area.reason', 'Road hazard at night');
    }

    public function test_saved_location_can_be_updated_and_deleted(): void
    {
        $admin = Admin::factory()->create();
        $area = AccidentProneArea::create([
            'latitude' => 11.2578671,
            'longitude' => 123.7142268,
            'barangay' => 'Tabagak',
            'municipality' => 'Madridejos',
            'province' => 'Cebu',
            'country' => 'Philippines',
            'place' => 'Tabagak, Madridejos, Cebu, Philippines',
            'reason' => 'Accident Prone Area',
        ]);

        $this->actingAs($admin)
            ->putJson("/apa/locations/{$area->id}", [
                'reason' => '<strong>Road issue</strong>',
            ])
            ->assertOk()
            ->assertJsonPath('area.reason', 'Road issue');

        $this->assertDatabaseHas('accident_prone_areas', [
            'id' => $area->id,
            'reason' => 'Road issue',
        ]);

        $this->deleteJson("/apa/locations/{$area->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Accident-prone area deleted.');

        $this->assertDatabaseMissing('accident_prone_areas', ['id' => $area->id]);
    }
}
