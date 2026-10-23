<?php

namespace App\Http\Controllers;

use App\Models\BrakeEvent;
use App\Models\AccidentProneArea;
use App\Support\BarangayLocator;

class AnalyticsController extends Controller
{
    public function index()
    {
        $datasetEvents = BrakeEvent::query()->orderBy('timestamp')->get();
        $availableEventDates = $datasetEvents
            ->filter(fn (BrakeEvent $event) => $event->timestamp !== null)
            ->map(fn (BrakeEvent $event) => $event->timestamp->format('Y-m-d'))
            ->unique()
            ->values();
        $availableEventDates = $availableEventDates->all();
        rsort($availableEventDates, SORT_STRING);

        $severityCounts = [
            'soft' => 0,
            'moderate' => 0,
            'hard' => 0,
            'emergency' => 0,
        ];
        $mappedEvents = $datasetEvents->filter(function (BrakeEvent $event) use (&$severityCounts) {
            $latitude = (float) $event->latitude;
            $longitude = (float) $event->longitude;
            if ($event->latitude === null || $event->longitude === null || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                return false;
            }

            if (!in_array($event->municipality, ['Bantayan', 'Madridejos', 'Santa Fe'], true)) {
                return false;
            }

            $classification = strtolower(trim((string) $event->brake_classification));
            if ($classification === 'medium') {
                $classification = 'moderate';
            }
            if (array_key_exists($classification, $severityCounts)) {
                $severityCounts[$classification]++;
            }

            return true;
        })->map(fn (BrakeEvent $event) => [
            'id' => $event->id,
            'latitude' => (float) $event->latitude,
            'longitude' => (float) $event->longitude,
            'classification' => strtolower(trim((string) $event->brake_classification)) === 'medium'
                ? 'moderate'
                : strtolower(trim((string) $event->brake_classification)),
            'municipality' => $event->municipality,
            'barangay' => $event->barangay,
            'timestamp' => $event->timestamp?->toIso8601String(),
        ])->values();

        $barangayCounts = [];
        foreach ($mappedEvents as $event) {
            $barangay = trim((string) $event['barangay']);
            if ($barangay === '') {
                continue;
            }

            $key = $event['municipality'] . "\0" . $barangay;
            $barangayCounts[$key] ??= [
                'municipality' => $event['municipality'],
                'name' => $barangay,
                'counts' => array_fill_keys(array_keys($severityCounts), 0),
            ];
            $classification = $event['classification'] === 'medium' ? 'moderate' : $event['classification'];
            if (array_key_exists($classification, $barangayCounts[$key]['counts'])) {
                $barangayCounts[$key]['counts'][$classification]++;
            }
        }
        uasort($barangayCounts, fn (array $a, array $b) => array_sum($b['counts']) <=> array_sum($a['counts']));
        $topBarangays = array_slice(array_map(
            fn (array $barangay) => [
                'name' => $barangay['name'],
                'municipality' => $barangay['municipality'],
                'counts' => $barangay['counts'],
                'total' => array_sum($barangay['counts']),
            ],
            array_values($barangayCounts)
        ), 0, 10);

        $dataChecks = [
            'invalidLocationRows' => $datasetEvents->count() - $mappedEvents->count(),
            'maximumSpeed' => (float) $datasetEvents->max(fn (BrakeEvent $event) => (float) ($event->speed_kmh ?? $event->speed_kmph ?? 0)),
            'maximumDeceleration' => (float) $datasetEvents->max(fn (BrakeEvent $event) => (float) ($event->deceleration_force ?? 0)),
            'mappedLocations' => $mappedEvents->count(),
        ];
        $mapTilerKey = config('services.maptiler.key');
        $barangaysByMunicipality = app(BarangayLocator::class)->all();

        return view('analytics.index', compact('datasetEvents', 'mappedEvents', 'severityCounts', 'topBarangays', 'dataChecks', 'mapTilerKey', 'barangaysByMunicipality', 'availableEventDates'));
    }

    public function apa()
    {
        $mapTilerKey = config('services.maptiler.key');
        $municipalityBoundaries = [
            'Bantayan' => json_decode(file_get_contents(resource_path('geojson/bgy-702209000.json')), true, 512, JSON_THROW_ON_ERROR),
            'Madridejos' => json_decode(file_get_contents(resource_path('geojson/bgy-702228000.json')), true, 512, JSON_THROW_ON_ERROR),
            'Santa Fe' => json_decode(file_get_contents(resource_path('geojson/bgy-702244000.json')), true, 512, JSON_THROW_ON_ERROR),
        ];

        $accidentProneAreas = AccidentProneArea::query()->latest()->get();

        return view('analytics.apa', compact('mapTilerKey', 'municipalityBoundaries', 'accidentProneAreas'));
    }
}
