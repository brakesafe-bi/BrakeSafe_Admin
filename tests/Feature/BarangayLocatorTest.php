<?php

namespace Tests\Feature;

use App\Support\BarangayLocator;
use Tests\TestCase;

class BarangayLocatorTest extends TestCase
{
    public function test_barangay_catalog_contains_all_boundaries_for_the_three_municipalities(): void
    {
        $barangays = (new BarangayLocator())->all();

        $this->assertCount(25, $barangays['Bantayan']);
        $this->assertCount(14, $barangays['Madridejos']);
        $this->assertCount(10, $barangays['Santa Fe']);
        $this->assertContains('Bantigue (Poblacion)', $barangays['Bantayan']);
        $this->assertContains('Poblacion', $barangays['Madridejos']);
        $this->assertContains('Poblacion', $barangays['Santa Fe']);
    }

    public function test_coordinates_are_assigned_to_their_geojson_barangay(): void
    {
        $location = (new BarangayLocator())->locate(11.158879, 123.801215);

        $this->assertSame([
            'municipality' => 'Santa Fe',
            'barangay' => 'Poblacion',
        ], $location);
    }
}
