<?php

namespace App\Http\Controllers;

use App\Models\BrakeEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IncidentController extends Controller
{
    public function index()
    {
        $datasetInfo = null;
        if (Storage::disk('local')->exists('settings/dashboard-dataset.json')) {
            $savedDatasetInfo = json_decode(Storage::disk('local')->get('settings/dashboard-dataset.json'), true);
            if (is_array($savedDatasetInfo) && !empty($savedDatasetInfo['file_name'])) {
                $datasetInfo = $savedDatasetInfo;
            }
        }

        $eventIds = is_array($datasetInfo['event_ids'] ?? null) ? $datasetInfo['event_ids'] : null;
        $mappedEvents = $datasetInfo ? $this->mappedEventsQuery($eventIds)->get() : collect();
        $events = $mappedEvents->map(fn (BrakeEvent $event) => [
            'id' => $event->id,
            'latitude' => (float) $event->latitude,
            'longitude' => (float) $event->longitude,
            'classification' => strtolower((string) $event->brake_classification),
            'municipality' => $event->municipality,
            'barangay' => $event->barangay,
        ])->values();
        $rows = $mappedEvents->sortBy([
            ['timestamp', 'asc'],
            ['id', 'asc'],
        ])->values();

        $mapTilerKey = config('services.maptiler.key');

        return view('incidents.index', compact('datasetInfo', 'events', 'rows', 'mapTilerKey'));
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'municipality' => 'nullable|in:Bantayan,Madridejos,Santa Fe',
            'barangay' => 'nullable|string|max:255',
        ]);

        $datasetInfo = json_decode(Storage::disk('local')->get('settings/dashboard-dataset.json', '{}'), true);
        $eventIds = is_array($datasetInfo['event_ids'] ?? null) ? $datasetInfo['event_ids'] : null;
        $query = $this->filteredEventsQuery($validated, $eventIds);
        $fileName = basename((string) ($datasetInfo['file_name'] ?? 'brake_events.csv'));
        $exportName = pathinfo($fileName, PATHINFO_FILENAME) . '-export.csv';

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Latitude',
                'Longitude',
                'Speed(km/h)',
                'Deceleration(g)',
                'Brake Level',
                'Timestamp',
                'Direction',
                'Municipality',
                'Barangay',
            ]);

            $query->orderBy('timestamp')->orderBy('id')->chunk(500, function ($events) use ($output): void {
                foreach ($events as $event) {
                    fputcsv($output, [
                        rtrim(rtrim(number_format((float) $event->latitude, 7, '.', ''), '0'), '.'),
                        rtrim(rtrim(number_format((float) $event->longitude, 7, '.', ''), '0'), '.'),
                        number_format((float) $event->speed_kmh, 2, '.', ''),
                        number_format((float) $event->deceleration_force, 2, '.', ''),
                        strtolower($event->brake_classification === 'medium' ? 'moderate' : $event->brake_classification),
                        $event->timestamp->format('d/m/Y H:i'),
                        $event->direction ?? '',
                        $event->municipality,
                        $event->barangay ?? '',
                    ]);
                }
            });

            fclose($output);
        }, $exportName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function clearDataset()
    {
        $datasetPath = 'settings/dashboard-dataset.json';
        $datasetInfo = Storage::disk('local')->exists($datasetPath)
            ? json_decode(Storage::disk('local')->get($datasetPath), true)
            : null;

        if (!is_array($datasetInfo) || empty($datasetInfo['file_name'])) {
            return redirect()->route('brake-events.index')->with('status', 'There is no imported brake-event dataset to clear.');
        }

        $eventIds = $datasetInfo['event_ids'] ?? null;
        $query = $this->mappedEventsQuery(is_array($eventIds) ? $eventIds : null);
        $deletedCount = $query->delete();

        Storage::disk('local')->delete($datasetPath);
        $analyticsPath = 'settings/analytics-dataset.json';
        if (Storage::disk('local')->exists($analyticsPath)) {
            $analyticsInfo = json_decode(Storage::disk('local')->get($analyticsPath), true);
            if (
                is_array($analyticsInfo)
                && !empty($analyticsInfo['file_name'])
                && basename($analyticsInfo['file_name']) === basename($datasetInfo['file_name'])
            ) {
                Storage::disk('local')->delete($analyticsPath);
            }
        }

        return redirect()->route('brake-events.index')->with('status', "Cleared {$deletedCount} mapped brake events.");
    }

    private function mappedEventsQuery(?array $eventIds = null)
    {
        $query = BrakeEvent::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereIn('municipality', ['Bantayan', 'Madridejos', 'Santa Fe']);

        if ($eventIds !== null) {
            $query->whereIn('id', $eventIds);
        }

        return $query;
    }

    private function filteredEventsQuery(array $filters, ?array $eventIds = null)
    {
        $query = $this->mappedEventsQuery($eventIds);

        if (!empty($filters['municipality'])) {
            $query->where('municipality', $filters['municipality']);
        }
        if (!empty($filters['barangay'])) {
            $query->where('barangay', $filters['barangay']);
        }

        return $query;
    }
}
