<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Driver;
use App\Models\Trip;
use App\Services\Flespi\FlespiTripService;
use App\Helpers\PolylineDecoder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class TripController extends Controller
{
    public function __construct(
        private FlespiTripService $tripService
    ) {}

    /**
     * Display list of trips with filters
     */
    public function index(Request $request): View
    {
        $query = Trip::with(['device', 'driver']);

        // Apply filters
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        if ($request->filled('from')) {
            $query->where('start_time', '>=', $request->from . ' 00:00:00');
        }

        if ($request->filled('to')) {
            $query->where('start_time', '<=', $request->to . ' 23:59:59');
        }

        // Get trips with pagination
        $trips = $query->orderBy('start_time', 'desc')->paginate(20);

        // Calculate statistics
        $statsQuery = clone $query;
        $totalDistance = $statsQuery->sum('distance');
        $avgDistance = $statsQuery->avg('distance');
        $avgSpeed = $statsQuery->avg('avg_speed');

        // Get devices and drivers for filters
        $devices = Device::orderBy('name')->get();
        $drivers = Driver::orderBy('name')->get();

        return view('trips.index', compact(
            'trips',
            'devices',
            'drivers',
            'totalDistance',
            'avgDistance',
            'avgSpeed'
        ));
    }

    /**
     * Show trip details with route on map
     */
    public function show(Trip $trip): View
    {
        $trip->load(['device', 'driver']);

        // Get route points - check if route is encoded polyline string or array
        $routePoints = [];

        if ($trip->route) {
            // Check if metadata contains the encoded polyline
            $encodedRoute = null;
            if (is_string($trip->route)) {
                $encodedRoute = $trip->route;
            } elseif (is_array($trip->metadata) && isset($trip->metadata['route'])) {
                $encodedRoute = $trip->metadata['route'];
            }

            // Decode the polyline if we have one
            if ($encodedRoute && is_string($encodedRoute)) {
                $routePoints = PolylineDecoder::decode($encodedRoute);
            } elseif (is_array($trip->route) && isset($trip->route[0]['latitude'])) {
                // Already decoded array of points
                $routePoints = $trip->route;
            }
        }

        // Fallback: fetch from Flespi if no route available
        if (empty($routePoints) && $trip->flespi_interval_id && config('services.flespi.trip_calculator_id')) {
            try {
                $calcId = (int) config('services.flespi.trip_calculator_id');
                $messages = $this->tripService->getIntervalMessages(
                    $calcId,
                    $trip->device->flespi_device_id,
                    $trip->flespi_interval_id
                );
                $routePoints = $messages->toArray();
            } catch (\Exception $e) {
                // Log error but continue without route
                \Log::warning('Failed to fetch trip route: ' . $e->getMessage());
            }
        }

        return view('trips.show', compact('trip', 'routePoints'));
    }

    /**
     * Get trip route as JSON (for AJAX requests)
     */
    public function route(Trip $trip): JsonResponse
    {
        if ($trip->route) {
            return response()->json($trip->route);
        }

        if ($trip->flespi_interval_id && config('services.flespi.trip_calculator_id')) {
            try {
                $calcId = (int) config('services.flespi.trip_calculator_id');
                $messages = $this->tripService->getIntervalMessages(
                    $calcId,
                    $trip->device->flespi_device_id,
                    $trip->flespi_interval_id
                );

                $routePoints = $messages->toArray();

                // Store route
                $trip->update(['route' => $routePoints]);

                return response()->json($routePoints);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

        return response()->json([]);
    }

    /**
     * Sync trips from Flespi
     */
    public function sync(Request $request): RedirectResponse
    {
        $request->validate([
            'device_id' => 'nullable|exists:devices,id',
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        try {
            $calcId = (int) config('services.flespi.trip_calculator_id');

            if (!$calcId) {
                return redirect()->back()
                    ->with('error', 'Trip calculator not configured. Please run setup first.');
            }

            $days = $request->input('days', 7);
            $from = now()->subDays($days)->timestamp;
            $to = now()->timestamp;

            $synced = 0;

            if ($request->filled('device_id')) {
                // Sync specific device
                $device = Device::findOrFail($request->device_id);
                $synced = $this->syncDeviceTrips($device, $calcId, $from, $to);
            } else {
                // Sync all devices
                $devices = Device::all();
                foreach ($devices as $device) {
                    $synced += $this->syncDeviceTrips($device, $calcId, $from, $to);
                }
            }

            return redirect()->back()
                ->with('success', "Synced {$synced} trips from Flespi!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to sync trips: ' . $e->getMessage());
        }
    }

    /**
     * Sync trips for a specific device
     */
    private function syncDeviceTrips(Device $device, int $calcId, int $from, int $to): int
    {
        $intervals = $this->tripService->getDeviceIntervals(
            $calcId,
            $device->flespi_device_id,
            $from,
            $to,
            1000
        );

        $synced = 0;

        foreach ($intervals as $interval) {
            // Find driver based on assignment at trip start time
            $driverId = $device->current_driver_id;

            Trip::updateOrCreate(
                ['flespi_interval_id' => $interval['id']],
                [
                    'device_id' => $device->id,
                    'driver_id' => $driverId,
                    'start_time' => date('Y-m-d H:i:s', (int) $interval['begin']),
                    'end_time' => date('Y-m-d H:i:s', (int) $interval['end']),
                    'duration' => $interval['duration'],
                    'distance' => $interval['distance'], // Already in kilometers from Flespi
                    'avg_speed' => $interval['avg_speed'] ?? null,
                    'max_speed' => $interval['max_speed'] ?? null,
                    'start_latitude' => $interval['start_location']['latitude'],
                    'start_longitude' => $interval['start_location']['longitude'],
                    'end_latitude' => $interval['end_location']['latitude'],
                    'end_longitude' => $interval['end_location']['longitude'],
                    'route' => $interval['route'] ?? null,
                    'metadata' => $interval['metadata'],
                ]
            );

            $synced++;
        }

        return $synced;
    }
}
