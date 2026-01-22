<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\Flespi\FlespiDeviceService;
use Illuminate\Console\Command;

class SyncFlespiDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flespi:sync-devices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all devices from Flespi and update local database';

    public function __construct(
        private FlespiDeviceService $deviceService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Syncing devices from Flespi...');

        try {
            $devices = $this->deviceService->getAllDevices(false);
            $bar = $this->output->createProgressBar(count($devices));

            $synced = 0;
            $updated = 0;
            $created = 0;

            foreach ($devices as $flespiDevice) {
                // Get device location
                $location = $this->deviceService->getDeviceLocation($flespiDevice['id'], false);

                // Safely handle timestamp
                $timestamp = $location['timestamp'];
                $lastMessageAt = null;
                $status = 'offline';

                if ($timestamp && is_numeric($timestamp)) {
                    $lastMessageAt = date('Y-m-d H:i:s', (int)$timestamp);
                    $status = $timestamp >= now()->subMinutes(5)->timestamp ? 'online' : 'offline';
                }

                $device = Device::updateOrCreate(
                    ['flespi_device_id' => $flespiDevice['id']],
                    [
                        'name' => $flespiDevice['name'] ?? 'Device ' . $flespiDevice['id'],
                        'ident' => $flespiDevice['configuration']['ident'] ?? null,
                        'device_type_id' => $flespiDevice['device_type_id'] ?? null,
                        'last_latitude' => $location['latitude'],
                        'last_longitude' => $location['longitude'],
                        'last_speed' => $location['speed'],
                        'last_message_at' => $lastMessageAt,
                        'status' => $status,
                        'telemetry' => $location,
                    ]
                );

                if ($device->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                $synced++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✓ Successfully synced {$synced} devices!");
            $this->line("  - Created: {$created}");
            $this->line("  - Updated: {$updated}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to sync devices: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
