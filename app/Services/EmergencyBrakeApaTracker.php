<?php

namespace App\Services;

use App\Models\AccidentProneArea;
use App\Models\BrakeEvent;
use App\Models\EmergencyBrakeApaCluster;
use App\Support\BarangayLocator;
use Illuminate\Support\Facades\DB;

class EmergencyBrakeApaTracker
{
    private const APA_CREATION_THRESHOLD = 5;
    private const CLUSTER_RADIUS_METERS = 100;
    private const DUPLICATE_CHECK_RADIUS_METERS = 5000;
    private const ALLOWED_MUNICIPALITIES = ['Bantayan', 'Madridejos', 'Santa Fe'];

    public function __construct(private readonly BarangayLocator $barangayLocator)
    {
    }

    public function process(BrakeEvent $event): void
    {
        if (strtolower(trim((string) $event->brake_classification)) !== 'emergency') {
            return;
        }

        DB::transaction(function () use ($event): void {
            $lockedEvent = BrakeEvent::query()->lockForUpdate()->find($event->id);
            if (!$lockedEvent || $lockedEvent->apa_processed_at !== null) {
                return;
            }

            $latitude = (float) $lockedEvent->latitude;
            $longitude = (float) $lockedEvent->longitude;
            $location = $this->barangayLocator->locate($latitude, $longitude);
            if (
                $latitude < -90 || $latitude > 90
                || $longitude < -180 || $longitude > 180
                || $location === null
                || !in_array($location['municipality'], self::ALLOWED_MUNICIPALITIES, true)
            ) {
                $this->markProcessed($lockedEvent);
                return;
            }

            $areas = AccidentProneArea::query()->lockForUpdate()->get();
            $nearestArea = $areas
                ->map(fn (AccidentProneArea $area) => [
                    'area' => $area,
                    'distance' => $this->distanceInMeters(
                        $latitude,
                        $longitude,
                        (float) $area->latitude,
                        (float) $area->longitude
                    ),
                ])
                ->sortBy('distance')
                ->first();

            if ($nearestArea && $nearestArea['distance'] <= self::CLUSTER_RADIUS_METERS) {
                $area = $nearestArea['area'];
                $area->increment('emergency_hit_count');
                $this->markProcessed($lockedEvent);
                return;
            }

            if ($nearestArea && $nearestArea['distance'] <= self::DUPLICATE_CHECK_RADIUS_METERS) {
                $this->markProcessed($lockedEvent);
                return;
            }

            $cluster = EmergencyBrakeApaCluster::query()
                ->whereNull('accident_prone_area_id')
                ->lockForUpdate()
                ->get()
                ->map(fn (EmergencyBrakeApaCluster $candidate) => [
                    'cluster' => $candidate,
                    'distance' => $this->distanceInMeters(
                        $latitude,
                        $longitude,
                        (float) $candidate->latitude,
                        (float) $candidate->longitude
                    ),
                ])
                ->filter(fn (array $candidate) => $candidate['distance'] <= self::CLUSTER_RADIUS_METERS)
                ->sortBy('distance')
                ->first();

            if ($cluster) {
                $candidate = $cluster['cluster'];
                $candidate->increment('hit_count');
                $candidate->refresh();
            } else {
                $candidate = EmergencyBrakeApaCluster::create([
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'hit_count' => 1,
                ]);
            }

            if ($candidate->hit_count >= self::APA_CREATION_THRESHOLD) {
                $clusterLocation = $this->barangayLocator->locate(
                    (float) $candidate->latitude,
                    (float) $candidate->longitude
                );
                if ($clusterLocation === null) {
                    $this->markProcessed($lockedEvent);
                    return;
                }
                $area = AccidentProneArea::create([
                    'latitude' => $candidate->latitude,
                    'longitude' => $candidate->longitude,
                    'barangay' => $clusterLocation['barangay'],
                    'municipality' => $clusterLocation['municipality'],
                    'province' => 'Cebu',
                    'country' => 'Philippines',
                    'place' => "{$clusterLocation['barangay']}, {$clusterLocation['municipality']}, Cebu, Philippines",
                    'reason' => 'Auto-created after 5 emergency brake events within 100 m.',
                    'emergency_hit_count' => $candidate->hit_count,
                    'is_auto_generated' => true,
                ]);
                $candidate->update(['accident_prone_area_id' => $area->id]);
            }

            $this->markProcessed($lockedEvent);
        });
    }

    public function processMany(array $eventIds): void
    {
        BrakeEvent::query()
            ->whereIn('id', array_values(array_unique($eventIds)))
            ->orderBy('timestamp')
            ->orderBy('id')
            ->get()
            ->each(fn (BrakeEvent $event) => $this->process($event));
    }

    private function markProcessed(BrakeEvent $event): void
    {
        $event->apa_processed_at = now();
        $event->save();
    }

    private function distanceInMeters(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $radians = static fn (float $degrees): float => $degrees * M_PI / 180;
        $latitudeDelta = $radians($latitudeB - $latitudeA);
        $longitudeDelta = $radians($longitudeB - $longitudeA);
        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($radians($latitudeA)) * cos($radians($latitudeB))
            * sin($longitudeDelta / 2) ** 2;

        return 6371000 * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine));
    }
}
