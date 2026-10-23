<?php

namespace App\Console\Commands;

use App\Models\BrakeEvent;
use App\Support\BarangayLocator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignBrakeEventLocations extends Command
{
    protected $signature = 'brakesafe:assign-brake-event-locations {--apply : Persist coordinates-derived municipality and barangay values}';

    protected $description = 'Preview or assign database brake events to municipality barangay boundaries';

    public function handle(BarangayLocator $locator): int
    {
        $counts = [];
        $unmatched = 0;
        $process = function () use ($locator, &$counts, &$unmatched): void {
            BrakeEvent::query()->orderBy('id')->chunkById(500, function ($events) use ($locator, &$counts, &$unmatched): void {
                foreach ($events as $event) {
                    $location = $locator->locate((float) $event->latitude, (float) $event->longitude);
                    if ($location === null) {
                        $unmatched++;
                        continue;
                    }

                    $counts[$location['municipality']] = ($counts[$location['municipality']] ?? 0) + 1;
                    if ($this->option('apply')) {
                        $event->forceFill($location)->saveQuietly();
                    }
                }
            });
        };

        if ($this->option('apply')) {
            DB::transaction($process);
        } else {
            $process();
        }

        $this->table(
            ['Municipality', 'Matched events'],
            collect($counts)->map(fn (int $count, string $municipality) => [$municipality, $count])->values()->all()
        );
        $this->line("Unmatched events: {$unmatched}");
        $this->info($this->option('apply')
            ? 'Location assignments saved to the database.'
            : 'Preview only. Use --apply to save these database assignments.');

        return self::SUCCESS;
    }
}
