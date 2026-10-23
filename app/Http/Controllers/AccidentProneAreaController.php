<?php

namespace App\Http\Controllers;

use App\Models\AccidentProneArea;
use App\Support\BarangayLocator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccidentProneAreaController extends Controller
{
    private const ALLOWED_MUNICIPALITIES = ['Bantayan', 'Madridejos', 'Santa Fe'];

    public function store(Request $request, BarangayLocator $barangayLocator): JsonResponse
    {
        $request->merge(['reason' => $this->sanitizeText((string) $request->input('reason', ''))]);
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'reason' => ['required', 'string', 'max:255', 'not_regex:/<[^>]*>/'],
        ]);

        $location = $barangayLocator->locate(
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        if (
            $location === null
            || !in_array($location['municipality'], self::ALLOWED_MUNICIPALITIES, true)
        ) {
            throw ValidationException::withMessages([
                'latitude' => 'Choose a point inside a mapped barangay boundary in Bantayan, Madridejos, or Santa Fe.',
            ]);
        }

        $area = AccidentProneArea::create([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'barangay' => $location['barangay'],
            'municipality' => $location['municipality'],
            'province' => 'Cebu',
            'country' => 'Philippines',
            'place' => "{$location['barangay']}, {$location['municipality']}, Cebu, Philippines",
            'reason' => $validated['reason'],
            'emergency_hit_count' => 0,
            'is_auto_generated' => false,
        ]);

        return response()->json([
            'message' => 'Accident-prone area saved.',
            'area' => $area,
        ], 201);
    }

    public function update(Request $request, AccidentProneArea $accidentProneArea): JsonResponse
    {
        $request->merge(['reason' => $this->sanitizeText((string) $request->input('reason', ''))]);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255', 'not_regex:/<[^>]*>/'],
        ]);

        $accidentProneArea->update(['reason' => $validated['reason']]);

        return response()->json([
            'message' => 'Accident-prone area updated.',
            'area' => $accidentProneArea->fresh(),
        ]);
    }

    public function destroy(AccidentProneArea $accidentProneArea): JsonResponse
    {
        $accidentProneArea->delete();

        return response()->json(['message' => 'Accident-prone area deleted.']);
    }

    private function sanitizeText(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}
