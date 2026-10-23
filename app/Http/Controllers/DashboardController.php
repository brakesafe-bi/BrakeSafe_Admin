<?php

namespace App\Http\Controllers;

use App\Models\BrakeEvent;
use App\Models\AccidentProneArea;
use App\Support\BarangayLocator;
use App\Services\EmergencyBrakeApaTracker;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function dashboardOverview()
    {
        $municipalities = ['Bantayan', 'Madridejos', 'Santa Fe'];
        $classifications = [
            'Soft' => 'soft',
            'Moderate' => 'medium',
            'Hard' => 'hard',
            'Emergency' => 'emergency',
        ];
        $severityByMunicipality = [];
        $municipalityTotals = array_fill_keys($municipalities, 0);

        foreach ($classifications as $classification) {
            $severityByMunicipality[$classification] = array_fill_keys($municipalities, 0);
        }

        $datasetInfo = null;
        if (Storage::disk('local')->exists('settings/dashboard-dataset.json')) {
            $savedDatasetInfo = json_decode(Storage::disk('local')->get('settings/dashboard-dataset.json'), true);
            if (is_array($savedDatasetInfo) && !empty($savedDatasetInfo['file_name'])) {
                $datasetInfo = $savedDatasetInfo;
            }
        }

        $mappedEvents = $datasetInfo
            ? BrakeEvent::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereBetween('latitude', [-90, 90])
                ->whereBetween('longitude', [-180, 180])
                ->whereIn('municipality', $municipalities)
                ->get(['municipality', 'brake_classification'])
            : collect();

        foreach ($mappedEvents as $event) {
            $municipality = collect($municipalities)->first(
                fn ($name) => strtolower($name) === strtolower(trim((string) $event->municipality))
            );
            $classification = strtolower(trim((string) $event->brake_classification));

            if ($municipality === null || !isset($severityByMunicipality[$classification])) {
                continue;
            }

            $severityByMunicipality[$classification][$municipality]++;
            $municipalityTotals[$municipality]++;
        }

        $mappedEventCount = array_sum($municipalityTotals);
        $datasetEventCount = $datasetInfo ? BrakeEvent::count() : 0;
        $mapTilerKey = config('services.maptiler.key');
        return view('dashboard.index', [
            'municipalities' => $municipalities,
            'classifications' => array_keys($classifications),
            'severityByMunicipality' => $severityByMunicipality,
            'municipalityTotals' => $municipalityTotals,
            'mappedEventCount' => $mappedEventCount,
            'datasetEventCount' => $datasetEventCount,
            'datasetInfo' => $datasetInfo,
            'mapTilerKey' => $mapTilerKey,
            'proximityTargets' => $this->proximityTargets(),
        ]);
    }

    public function nearbyAlertTargets(): JsonResponse
    {
        return response()->json(['targets' => $this->proximityTargets()]);
    }

    private function proximityTargets()
    {
        $municipalities = ['Bantayan', 'Madridejos', 'Santa Fe'];
        $proximityBrakeEvents = BrakeEvent::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [-90, 90])
            ->whereBetween('longitude', [-180, 180])
            ->get(['id', 'latitude', 'longitude', 'barangay', 'municipality', 'brake_classification'])
            ->map(fn (BrakeEvent $event) => [
                'id' => "brake-event-{$event->id}",
                'latitude' => (float) $event->latitude,
                'longitude' => (float) $event->longitude,
                'type' => match (strtolower((string) $event->brake_classification)) {
                    'soft' => 'Soft',
                    'medium', 'moderate' => 'Moderate',
                    'hard' => 'Hard',
                    'emergency' => 'Emergency',
                    default => ucfirst(strtolower((string) $event->brake_classification)),
                },
                'place' => trim(implode(', ', array_filter([
                    $event->barangay,
                    $event->municipality,
                ]))),
            ])
            ->values();
        $proximityAreas = AccidentProneArea::query()
            ->get(['id', 'latitude', 'longitude', 'barangay', 'municipality', 'place'])
            ->map(fn (AccidentProneArea $area) => [
                'id' => "apa-{$area->id}",
                'latitude' => (float) $area->latitude,
                'longitude' => (float) $area->longitude,
                'type' => 'Accident-prone area',
                'place' => trim(implode(', ', array_filter([
                    $area->barangay,
                    $area->municipality,
                ]))) ?: $area->place,
            ])
            ->values();

        return $proximityBrakeEvents->concat($proximityAreas)->values();
    }

    public function importBrakeEvents(Request $request, EmergencyBrakeApaTracker $apaTracker)
    {
        $returnTo = $request->input('return_to');
        $redirectRoute = $returnTo === 'dashboard'
            ? 'dashboard'
            : 'brake-events.index';
        $validator = Validator::make($request->all(), [
            'brake_csv' => 'required|file|mimes:csv,txt,xlsx',
        ]);
        if ($validator->fails()) {
            return redirect()->route($redirectRoute)->with('import_error', $validator->errors()->first('brake_csv'));
        }

        $file = $validator->validated()['brake_csv'];
        try {
            $rows = strtolower($file->getClientOriginalExtension()) === 'xlsx'
                ? $this->readXlsxRows($file->getRealPath())
                : array_map('str_getcsv', file($file->getRealPath()));
        } catch (\RuntimeException $exception) {
            return redirect()->route($redirectRoute)->with('import_error', $exception->getMessage());
        }

        if (empty($rows) || count($rows) === 1 && count(array_filter($rows[0], fn($value) => trim((string) $value) !== '')) === 0) {
            return redirect()->route($redirectRoute)->with('import_error', 'The uploaded file is empty or invalid.');
        }

        $header = array_map('trim', array_shift($rows));
        if (isset($header[0])) {
            $header[0] = preg_replace('/^(?:\xEF\xBB\xBF|\xC3\xAF\xC2\xBB\xC2\xBF)/', '', $header[0]);
        }
        $columns = array_map('strtolower', $header);
        $acceptedColumnFormats = [
            ['latitude', 'longitude', 'speed(km/h)', 'deceleration(g)', 'brake level', 'timestamp', 'direction'],
            ['latitude', 'longitude', 'speed(km/h)', 'deceleration(g)', 'brake level', 'timestamp', 'direction', 'municipality', 'barangay'],
        ];

        if (!in_array($columns, $acceptedColumnFormats, true)) {
            return redirect()->route($redirectRoute)->with(
                'import_error',
                'Import declined. Use one of these column formats: Latitude, Longitude, Speed(km/h), Deceleration(g), Brake Level, Timestamp, Direction; or add Municipality and Barangay at the end.'
            );
        }

        $columnIndex = array_flip($columns);
        $candidateEventKeys = [];
        $seenEventKeys = [];
        $parsedTimestamps = [];
        foreach ($rows as $rowIndex => $row) {
            if (count(array_filter($row, fn($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $latitude = trim($row[$columnIndex['latitude']] ?? '');
            $longitude = trim($row[$columnIndex['longitude']] ?? '');
            if (
                $latitude === ''
                || $longitude === ''
                || !is_numeric($latitude)
                || !is_numeric($longitude)
                || (float) $latitude < -90
                || (float) $latitude > 90
                || (float) $longitude < -180
                || (float) $longitude > 180
            ) {
                continue;
            }

            $parsedTimestamp = $this->parseBrakeEventTimestamp(trim($row[$columnIndex['timestamp']] ?? ''));
            if (!$parsedTimestamp) {
                continue;
            }

            $eventKey = implode('|', [
                number_format((float) $latitude, 7, '.', ''),
                number_format((float) $longitude, 7, '.', ''),
                $parsedTimestamp->format('Y-m-d H:i:s'),
            ]);
            if (isset($seenEventKeys[$eventKey])) {
                return redirect()->route($redirectRoute)->with(
                    'import_error',
                    'Import declined. The file contains duplicate brake-event data.'
                );
            }

            $seenEventKeys[$eventKey] = true;
            $candidateEventKeys[] = [
                number_format((float) $latitude, 7, '.', ''),
                number_format((float) $longitude, 7, '.', ''),
                $parsedTimestamp->format('Y-m-d H:i:s'),
            ];
            $parsedTimestamps[$rowIndex] = $parsedTimestamp;
        }

        foreach (array_chunk($candidateEventKeys, 100) as $eventKeyChunk) {
            $duplicateExists = BrakeEvent::query()
                ->where(function ($query) use ($eventKeyChunk): void {
                    foreach ($eventKeyChunk as [$latitude, $longitude, $timestamp]) {
                        $query->orWhere(function ($match) use ($latitude, $longitude, $timestamp): void {
                            $match->where('latitude', $latitude)
                                ->where('longitude', $longitude)
                                ->where('timestamp', $timestamp);
                        });
                    }
                })
                ->exists();

            if ($duplicateExists) {
                return redirect()->route($redirectRoute)->with(
                    'import_error',
                    'Import declined. One or more brake events in this file are already in the database.'
                );
            }
        }

        $importedCount = 0;
        $importedEventIds = [];
        $invalidLocationRows = 0;
        $barangayLocator = app(BarangayLocator::class);

        foreach ($rows as $rowIndex => $row) {
            if (count(array_filter($row, fn($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $latitude = trim($row[$columnIndex['latitude']] ?? '');
            $longitude = trim($row[$columnIndex['longitude']] ?? '');
            $timestamp = trim($row[$columnIndex['timestamp']] ?? '');
            $municipality = isset($columnIndex['municipality']) ? trim($row[$columnIndex['municipality']] ?? '') : '';
            $speed = isset($columnIndex['speed(km/h)']) ? trim($row[$columnIndex['speed(km/h)']] ?? '') : null;
            $deceleration = isset($columnIndex['deceleration(g)']) ? trim($row[$columnIndex['deceleration(g)']] ?? '') : null;
            $brakeLevel = isset($columnIndex['brake level']) ? trim($row[$columnIndex['brake level']] ?? '') : null;
            $vehicleId = isset($columnIndex['vehicle_id']) ? trim($row[$columnIndex['vehicle_id']] ?? '') : 'unknown';
            $barangay = isset($columnIndex['barangay']) ? trim($row[$columnIndex['barangay']] ?? '') : null;
            $direction = isset($columnIndex['direction']) ? trim($row[$columnIndex['direction']] ?? '') : null;

            if (
                $latitude === ''
                || $longitude === ''
                || !is_numeric($latitude)
                || !is_numeric($longitude)
                || (float) $latitude < -90
                || (float) $latitude > 90
                || (float) $longitude < -180
                || (float) $longitude > 180
            ) {
                $invalidLocationRows++;
                continue;
            }

            $parsedTimestamp = $parsedTimestamps[$rowIndex] ?? null;
            if (!$parsedTimestamp) {
                continue;
            }

            $brakeClassification = $brakeLevel ? strtolower(trim($brakeLevel)) : 'soft';
            if ($brakeClassification === 'moderate') {
                $brakeClassification = 'medium';
            }
            if (!in_array($brakeClassification, ['soft', 'medium', 'hard', 'emergency'], true)) {
                $brakeClassification = 'soft';
            }

            $location = $barangayLocator->fillLocation((float) $latitude, (float) $longitude, $municipality, $barangay);
            $municipality = $location['municipality'];
            $barangay = $location['barangay'];

            $event = BrakeEvent::firstOrNew([
                'latitude' => $latitude,
                'longitude' => $longitude,
                'timestamp' => $parsedTimestamp,
            ]);

            $event->fill([
                'vehicle_id' => $vehicleId ?: 'unknown',
                'device_id' => 'csv-import',
                'speed_kmh' => is_numeric($speed) ? (float) $speed : 0.00,
                'deceleration_force' => is_numeric($deceleration) ? (float) $deceleration : 0.00,
                'brake_classification' => $brakeClassification,
                'municipality' => $municipality,
                'barangay' => $barangay,
                'direction' => $direction,
                'timestamp' => $parsedTimestamp,
            ]);

            $event->save();
            $importedEventIds[] = $event->id;
            $importedCount++;
        }

        if ($importedCount > 0) {
            $apaTracker->processMany($importedEventIds);
            Storage::disk('local')->put('settings/analytics-dataset.json', json_encode([
                'file_name' => basename($file->getClientOriginalName()),
                'imported_at' => now()->toIso8601String(),
                'event_ids' => array_values(array_unique($importedEventIds)),
                'invalid_location_rows' => $invalidLocationRows,
            ], JSON_THROW_ON_ERROR));
        }

        if (in_array($returnTo, ['dashboard', 'brake-events'], true) && $importedCount > 0) {
            Storage::disk('local')->put('settings/dashboard-dataset.json', json_encode([
                'file_name' => basename($file->getClientOriginalName()),
                'imported_at' => now()->toIso8601String(),
                'event_ids' => array_values(array_unique($importedEventIds)),
            ], JSON_THROW_ON_ERROR));
        }

        return redirect()->route($redirectRoute)->with('status', "Imported {$importedCount} brake events.");
    }

    private function parseBrakeEventTimestamp(string $timestamp): ?Carbon
    {
        if (is_numeric($timestamp) && (float) $timestamp > 10000) {
            return Carbon::createFromTimestampUTC((int) round(((float) $timestamp - 25569) * 86400));
        }

        foreach (['!d/m/Y H:i:s', '!d/m/Y H:i'] as $format) {
            try {
                $parsedTimestamp = Carbon::createFromFormat($format, $timestamp);
                if ($parsedTimestamp !== false) {
                    return $parsedTimestamp;
                }
            } catch (\Exception $exception) {
                continue;
            }
        }

        try {
            return Carbon::parse($timestamp);
        } catch (\Exception $exception) {
            return null;
        }
    }

    private function readXlsxRows(string $path): array
    {
        $archive = new \ZipArchive();
        if ($archive->open($path) !== true) {
            throw new \RuntimeException('Unable to open the uploaded Excel workbook.');
        }

        try {
            $sharedStrings = [];
            $parseXml = function (string $xml, string $errorMessage): \SimpleXMLElement {
                $previousErrorMode = libxml_use_internal_errors(true);
                try {
                    $document = simplexml_load_string($xml);
                    if ($document === false) {
                        throw new \RuntimeException($errorMessage);
                    }

                    return $document;
                } finally {
                    libxml_clear_errors();
                    libxml_use_internal_errors($previousErrorMode);
                }
            };

            $sharedStringsXml = $archive->getFromName('xl/sharedStrings.xml');
            if ($sharedStringsXml !== false) {
                $sharedStringsDocument = $parseXml($sharedStringsXml, 'The Excel workbook contains invalid shared strings.');
                $sharedStringsDocument->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                foreach ($sharedStringsDocument->xpath('//x:si') as $string) {
                    $string->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                    $sharedStrings[] = implode('', array_map('strval', $string->xpath('.//x:t')));
                }
            }

            $worksheetXml = $archive->getFromName('xl/worksheets/sheet1.xml');
            if ($worksheetXml === false) {
                throw new \RuntimeException('The Excel workbook does not contain a readable first worksheet.');
            }
            $worksheet = $parseXml($worksheetXml, 'The Excel workbook contains invalid worksheet data.');
            $worksheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

            $rows = [];
            foreach ($worksheet->xpath('//x:sheetData/x:row') as $row) {
                $row->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $values = [];
                foreach ($row->xpath('./x:c') as $cell) {
                    $reference = (string) $cell['r'];
                    preg_match('/^[A-Z]+/', $reference, $columnMatch);
                    if (!$columnMatch) {
                        continue;
                    }

                    $columnIndex = 0;
                    foreach (str_split($columnMatch[0]) as $letter) {
                        $columnIndex = ($columnIndex * 26) + (ord($letter) - ord('A') + 1);
                    }
                    $columnIndex--;

                    $type = (string) $cell['t'];
                    $value = (string) ($cell->v ?? '');
                    if ($type === 's') {
                        $value = $sharedStrings[(int) $value] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $cell->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                        $value = implode('', array_map('strval', $cell->xpath('.//x:t')));
                    }
                    $values[$columnIndex] = $value;
                }

                if ($values) {
                    ksort($values);
                    $rows[] = array_replace(array_fill(0, max(array_keys($values)) + 1, ''), $values);
                }
            }

            return $rows;
        } finally {
            $archive->close();
        }
    }
}
