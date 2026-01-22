<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Flespi\FlespiTripService;
use Illuminate\Console\Command;

class DebugFlespiIntervals extends Command
{
    protected $signature = 'flespi:debug-intervals {device_id}';
    protected $description = 'Debug Flespi intervals API response structure';

    public function __construct(
        private FlespiTripService $tripService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $calcId = (int) config('services.flespi.trip_calculator_id');
        $deviceId = (int) $this->argument('device_id');

        if (!$calcId) {
            $this->error('Trip calculator not configured.');
            return Command::FAILURE;
        }

        $this->info("Fetching intervals for device {$deviceId} from calculator {$calcId}...");

        $from = now()->subDays(7)->timestamp;
        $to = now()->timestamp;

        try {
            $intervals = $this->tripService->getDeviceIntervals($calcId, $deviceId, $from, $to, 5);

            $this->info("Found " . count($intervals) . " intervals");
            $this->newLine();

            foreach ($intervals as $index => $interval) {
                $this->info("=== Interval #" . ($index + 1) . " ===");
                $this->line("Raw data structure:");
                $this->line(json_encode($interval['metadata'] ?? $interval, JSON_PRETTY_PRINT));
                $this->newLine();

                $this->line("Formatted data:");
                $this->line("  ID: " . ($interval['id'] ?? 'null'));
                $this->line("  Duration: " . ($interval['duration'] ?? 'null') . " seconds");
                $this->line("  Distance: " . ($interval['distance'] ?? 'null') . " meters");
                $this->line("  Max Speed: " . ($interval['max_speed'] ?? 'null'));
                $this->line("  Avg Speed: " . ($interval['avg_speed'] ?? 'null'));
                $this->newLine(2);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
