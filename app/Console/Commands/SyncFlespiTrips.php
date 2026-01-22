<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Trip;
use App\Services\Flespi\FlespiTripService;
use Illuminate\Console\Command;

class SyncFlespiTrips extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flespi:sync-trips
                            {--device= : Specific device ID to sync}
                            {--from= : Start date (YYYY-MM-DD)}
                            {--to= : End date (YYYY-MM-DD)}
                            {--days=7 : Number of days to sync (default: 7)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync trips from Flespi for all or specific devices';

    public function __construct(
        private FlespiTripService $tripService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $calcId = config('services.flespi.trip_calculator_id');

        if (!$calcId) {
            $this->error('Trip calculator not configured. Please set FLESPI_TRIP_CALC_ID in .env file.');
            return Command::FAILURE;
        }

        // Calculate date range
        if ($this->option('from') && $this->option('to')) {
            $from = strtotime($this->option('from') . ' 00:00:00');
            $to = strtotime($this->option('to') . ' 23:59:59');
        } else {
            $days = (int) $this->option('days');
            $from = now()->subDays($days)->timestamp;
            $to = now()->timestamp;
        }

        $fromDate = date('Y-m-d H:i', $from);
        $toDate = date('Y-m-d H:i', $to);

        $this->info("Syncing trips from {$fromDate} to {$toDate}");

        try {
            $synced = 0;
            $created = 0;
            $updated = 0;

            if ($this->option('device')) {
                // Sync specific device
                $device = Device::findOrFail($this->option('device'));
                $this->info("Syncing trips for device: {$device->name}");

                $result = $this->syncDeviceTrips($device, $calcId, $from, $to);
                $synced = $result['synced'];
                $created = $result['created'];
                $updated = $result['updated'];
            } else {
                // Sync all devices
                $devices = Device::all();
                $bar = $this->output->createProgressBar(count($devices));

                foreach ($devices as $device) {
                    $result = $this->syncDeviceTrips($device, $calcId, $from, $to);
                    $synced += $result['synced'];
                    $created += $result['created'];
                    $updated += $result['updated'];
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine(2);
            }

            $this->info("✓ Successfully synced {$synced} trips!");
            $this->line("  - Created: {$created}");
            $this->line("  - Updated: {$updated}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to sync trips: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Sync trips for a specific device
     */
    private function syncDeviceTrips(Device $device, int $calcId, int $from, int $to): array
    {
        $intervals = $this->tripService->getDeviceIntervals(
            $calcId,
            $device->flespi_device_id,
            $from,
            $to,
            1000
        );

        $synced = 0;
        $created = 0;
        $updated = 0;

        foreach ($intervals as $interval) {
            $trip = Trip::updateOrCreate(
                ['flespi_interval_id' => $interval['id']],
                [
                    'device_id' => $device->id,
                    'driver_id' => $device->current_driver_id,
                    'start_time' => date('Y-m-d H:i:s', $interval['begin']),
                    'end_time' => date('Y-m-d H:i:s', $interval['end']),
                    'duration' => $interval['duration'],
                    'distance' => $interval['distance'] / 1000, // Convert meters to km
                    'avg_speed' => $interval['avg_speed'] ?? null,
                    'max_speed' => $interval['max_speed'] ?? null,
                    'start_latitude' => $interval['start_location']['latitude'],
                    'start_longitude' => $interval['start_location']['longitude'],
                    'end_latitude' => $interval['end_location']['latitude'],
                    'end_longitude' => $interval['end_location']['longitude'],
                    'metadata' => $interval['metadata'],
                ]
            );

            if ($trip->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }

            $synced++;
        }

        return [
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
        ];
    }
}
