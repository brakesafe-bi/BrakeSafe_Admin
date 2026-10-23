<?php

namespace App\Support;

use RuntimeException;

class BarangayLocator
{
    private const MUNICIPALITIES = [
        'bgy-702209000.json' => 'Bantayan',
        'bgy-702228000.json' => 'Madridejos',
        'bgy-702244000.json' => 'Santa Fe',
    ];

    private static ?array $barangays = null;

    public function locate(float $latitude, float $longitude): ?array
    {
        foreach ($this->barangays() as $municipality => $barangays) {
            foreach ($barangays as $barangay) {
                [$minLongitude, $minLatitude, $maxLongitude, $maxLatitude] = $barangay['bounds'];
                if (
                    $longitude < $minLongitude
                    || $longitude > $maxLongitude
                    || $latitude < $minLatitude
                    || $latitude > $maxLatitude
                ) {
                    continue;
                }

                if ($this->containsPoint($longitude, $latitude, $barangay['geometry'])) {
                    return [
                        'municipality' => $municipality,
                        'barangay' => $this->displayName($municipality, $barangay['name']),
                    ];
                }
            }
        }

        return null;
    }

    public function fillLocation(float $latitude, float $longitude, ?string $municipality = null, ?string $barangay = null): array
    {
        $location = $this->locate($latitude, $longitude);
        if ($location !== null) {
            return $location;
        }

        $municipality = trim((string) ($municipality ?? ''));
        if ($municipality === '' || !in_array($municipality, ['Bantayan', 'Madridejos', 'Santa Fe'], true)) {
            $municipality = 'Madridejos';
        }

        $barangay = trim((string) ($barangay ?? ''));

        return [
            'municipality' => $municipality,
            'barangay' => $barangay === '' ? 'Unknown' : $barangay,
        ];
    }

    public function all(): array
    {
        $locations = [];
        foreach ($this->barangays() as $municipality => $barangays) {
            $locations[$municipality] = array_values(array_map(
                fn (array $barangay) => $this->displayName($municipality, $barangay['name']),
                $barangays
            ));
            sort($locations[$municipality], SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $locations;
    }

    private function displayName(string $municipality, string $name): string
    {
        if ($municipality === 'Bantayan' && in_array($name, ['Bantigue', 'Binaobao', 'Suba', 'Ticad'], true)) {
            return "{$name} (Poblacion)";
        }

        return $name;
    }

    private function barangays(): array
    {
        if (self::$barangays !== null) {
            return self::$barangays;
        }

        $barangays = [];
        foreach (self::MUNICIPALITIES as $fileName => $municipality) {
            $path = resource_path('geojson/' . $fileName);
            $contents = file_get_contents($path);
            if ($contents === false) {
                throw new RuntimeException("Unable to read barangay boundaries: {$path}");
            }

            $collection = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            foreach ($collection['features'] ?? [] as $feature) {
                $geometry = $feature['geometry'] ?? null;
                $name = trim((string) ($feature['properties']['adm4_en'] ?? ''));
                if ($name === '' || !is_array($geometry)) {
                    continue;
                }

                $bounds = $this->bounds($geometry['coordinates'] ?? []);
                if ($bounds !== null) {
                    $barangays[$municipality][] = [
                        'name' => $name,
                        'geometry' => $geometry,
                        'bounds' => $bounds,
                    ];
                }
            }
        }

        return self::$barangays = $barangays;
    }

    private function bounds(array $coordinates): ?array
    {
        $longitudes = [];
        $latitudes = [];
        $collect = function (array $values) use (&$collect, &$longitudes, &$latitudes): void {
            if (
                count($values) >= 2
                && is_numeric($values[0])
                && is_numeric($values[1])
            ) {
                $longitudes[] = (float) $values[0];
                $latitudes[] = (float) $values[1];
                return;
            }

            foreach ($values as $value) {
                if (is_array($value)) {
                    $collect($value);
                }
            }
        };
        $collect($coordinates);

        if ($longitudes === []) {
            return null;
        }

        return [
            min($longitudes),
            min($latitudes),
            max($longitudes),
            max($latitudes),
        ];
    }

    private function containsPoint(float $longitude, float $latitude, array $geometry): bool
    {
        $polygons = match ($geometry['type'] ?? null) {
            'Polygon' => [$geometry['coordinates'] ?? []],
            'MultiPolygon' => $geometry['coordinates'] ?? [],
            default => [],
        };

        foreach ($polygons as $rings) {
            if (!isset($rings[0]) || !$this->insideRing($longitude, $latitude, $rings[0])) {
                continue;
            }

            $insideHole = false;
            foreach (array_slice($rings, 1) as $hole) {
                if ($this->insideRing($longitude, $latitude, $hole)) {
                    $insideHole = true;
                    break;
                }
            }

            if (!$insideHole) {
                return true;
            }
        }

        return false;
    }

    private function insideRing(float $longitude, float $latitude, array $ring): bool
    {
        $inside = false;
        $count = count($ring);
        for ($index = 0, $previous = $count - 1; $index < $count; $previous = $index++) {
            $x1 = (float) $ring[$index][0];
            $y1 = (float) $ring[$index][1];
            $x2 = (float) $ring[$previous][0];
            $y2 = (float) $ring[$previous][1];

            $cross = ($longitude - $x1) * ($y2 - $y1) - ($latitude - $y1) * ($x2 - $x1);
            if (
                abs($cross) < 1.0e-10
                && $longitude >= min($x1, $x2)
                && $longitude <= max($x1, $x2)
                && $latitude >= min($y1, $y2)
                && $latitude <= max($y1, $y2)
            ) {
                return true;
            }

            $crossesLatitude = (($y1 > $latitude) !== ($y2 > $latitude));
            if ($crossesLatitude && $longitude < ($x2 - $x1) * ($latitude - $y1) / ($y2 - $y1) + $x1) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}
