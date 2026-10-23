<?php

namespace App\Http\Controllers;

use App\Models\BrakeEvent;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebServerController extends Controller
{
    public function index()
    {
        return view('web-server.index');
    }

    public function downloadBrakeEvents(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Unable to open the brake-events CSV output stream.');
            }

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

            BrakeEvent::query()
                ->orderBy('id')
                ->cursor()
                ->each(function (BrakeEvent $event) use ($output) {
                    fputcsv($output, [
                        $event->latitude,
                        $event->longitude,
                        $event->speed_kmh ?? $event->speed_kmph,
                        $event->deceleration_force,
                        $event->brake_classification,
                        $event->timestamp?->format('d/m/Y H:i:s'),
                        $event->direction,
                        $event->municipality,
                        $event->barangay,
                    ]);
                });

            fclose($output);
        }, 'brake-events.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
