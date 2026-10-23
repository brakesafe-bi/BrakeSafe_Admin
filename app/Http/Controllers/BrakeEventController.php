<?php

namespace App\Http\Controllers;

use App\Models\BrakeEvent;
use App\Services\EmergencyBrakeApaTracker;
use App\Support\BarangayLocator;
use Illuminate\Http\Request;

class BrakeEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        Request $request,
        BarangayLocator $barangayLocator,
        EmergencyBrakeApaTracker $apaTracker
    )
    {
        $validated = $request->validate([
            'vehicle_id' => ['nullable', 'string', 'max:255'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'speed_kmph' => ['nullable', 'numeric', 'min:0'],
            'deceleration_g' => ['required', 'numeric', 'min:0'],
            'brake_level' => ['required', 'in:normal,soft,moderate,hard,emergency'],
            'direction' => ['nullable', 'in:N,NE,E,SE,S,SW,W,NW'],
            'timestamp' => ['nullable', 'date_format:Y-m-d\TH:i:s\Z'],
        ]);

        if (strtolower($validated['brake_level']) === 'normal') {
            return response()->json([
                'success' => true,
                'message' => 'Normal driving sample ignored.',
                'data' => null,
            ]);
        }

        $location = $barangayLocator->locate(
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );
        if ($location === null || !in_array($location['municipality'], ['Bantayan', 'Madridejos', 'Santa Fe'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'The event location is outside the supported barangay boundaries.',
            ], 422);
        }

        $classification = strtolower($validated['brake_level']) === 'moderate'
            ? 'medium'
            : strtolower($validated['brake_level']);
        $event = BrakeEvent::create([
            'vehicle_id' => $validated['vehicle_id'] ?? 'unknown',
            'device_id' => $validated['device_id'] ?? 'esp32',
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'speed_kmh' => $validated['speed_kmph'] ?? 0,
            'deceleration_force' => $validated['deceleration_g'],
            'brake_classification' => $classification,
            'municipality' => $location['municipality'],
            'barangay' => $location['barangay'],
            'direction' => $validated['direction'] ?? null,
            'timestamp' => isset($validated['timestamp'])
                ? \Carbon\Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $validated['timestamp'])
                : now(),
        ]);

        $apaTracker->process($event);

        \Illuminate\Support\Facades\Log::info('Brake event recorded', [
            'brake_event_id' => $event->id,
            'brake_level' => $event->brake_classification,
            'deceleration' => $event->deceleration_force,
            'location' => [
                'lat' => $event->latitude,
                'lng' => $event->longitude,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Brake event recorded successfully',
            'data' => $event,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(BrakeEvent $brakeEvent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BrakeEvent $brakeEvent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BrakeEvent $brakeEvent)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BrakeEvent $brakeEvent)
    {
        //
    }
}
