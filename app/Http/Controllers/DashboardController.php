<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Driver;
use App\Models\Trip;
use App\Services\Flespi\FlespiDeviceService;
use App\Services\Flespi\FlespiTripService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function __construct(
        private FlespiDeviceService $deviceService,
        private FlespiTripService $tripService
    ) {}

    /**
     * Display the dashboard
     */
    public function index(): View
    {
        // Get statistics
        $totalDevices = Device::count();
        $onlineDevices = Device::where('status', 'online')->count();
        $totalDrivers = Driver::count();
        $tripsToday = Trip::whereDate('start_time', today())->count();

        // Get devices with location for map
        $devices = Device::with('currentDriver')
            ->whereNotNull('last_latitude')
            ->whereNotNull('last_longitude')
            ->get();

        // Get recent trips
        $recentTrips = Trip::with(['device', 'driver'])
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'totalDevices',
            'onlineDevices',
            'totalDrivers',
            'tripsToday',
            'devices',
            'recentTrips'
        ));
    }

    /**
     * Show setup wizard
     */
    public function setup(): View
    {
        $hasToken = !empty(config('services.flespi.token'));
        $hasCalculator = !empty(config('services.flespi.trip_calculator_id'));
        $deviceCount = Device::count();

        return view('dashboard.setup', compact('hasToken', 'hasCalculator', 'deviceCount'));
    }

    /**
     * Create trip calculator in Flespi
     */
    public function createCalculator(Request $request): RedirectResponse
    {
        try {
            $calculator = $this->tripService->createTripCalculator('Fleet Trip Detector');

            // Update .env file with calculator ID (in production, use config file)
            $envFile = base_path('.env');
            $envContent = file_get_contents($envFile);
            $envContent = preg_replace(
                '/FLESPI_TRIP_CALC_ID=.*/',
                'FLESPI_TRIP_CALC_ID=' . $calculator['id'],
                $envContent
            );
            file_put_contents($envFile, $envContent);

            // Assign all devices to calculator
            $deviceIds = Device::pluck('flespi_device_id')->toArray();
            if (!empty($deviceIds)) {
                $this->tripService->assignDevicesToCalculator($calculator['id'], $deviceIds);
            }

            return redirect()->route('setup')
                ->with('success', 'Trip calculator created successfully! ID: ' . $calculator['id']);

        } catch (\Exception $e) {
            return redirect()->route('setup')
                ->with('error', 'Failed to create calculator: ' . $e->getMessage());
        }
    }

    /**
     * Sync devices from Flespi
     */
    public function syncDevices(Request $request): RedirectResponse
    {
        try {
            $devices = $this->deviceService->getAllDevices(false);
            $synced = 0;

            foreach ($devices as $flespiDevice) {
                // Get device location
                $location = $this->deviceService->getDeviceLocation($flespiDevice['id'], false);

                Device::updateOrCreate(
                    ['flespi_device_id' => $flespiDevice['id']],
                    [
                        'name' => $flespiDevice['name'] ?? 'Device ' . $flespiDevice['id'],
                        'ident' => $flespiDevice['configuration']['ident'] ?? null,
                        'device_type_id' => $flespiDevice['device_type_id'] ?? null,
                        'last_latitude' => $location['latitude'],
                        'last_longitude' => $location['longitude'],
                        'last_speed' => $location['speed'],
                        'last_message_at' => $location['timestamp'] ? date('Y-m-d H:i:s', $location['timestamp']) : null,
                        'status' => $location['timestamp'] && $location['timestamp'] >= now()->subMinutes(5)->timestamp ? 'online' : 'offline',
                    ]
                );

                $synced++;
            }

            return redirect()->back()
                ->with('success', "Synced {$synced} devices from Flespi!");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to sync devices: ' . $e->getMessage());
        }
    }
}
