<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Services\Flespi\FlespiDeviceService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class DeviceController extends Controller
{
    public function __construct(
        private FlespiDeviceService $deviceService
    ) {}

    /**
     * Display a listing of devices
     */
    public function index(): View
    {
        $devices = Device::with('currentDriver')
            ->orderBy('name')
            ->paginate(12);

        return view('devices.index', compact('devices'));
    }

    /**
     * Display the specified device
     */
    public function show(Device $device): View
    {
        $device->load(['currentDriver', 'trips' => function($query) {
            $query->orderBy('start_time', 'desc')->limit(10);
        }]);

        return view('devices.show', compact('device'));
    }

    /**
     * Get device telemetry (API endpoint)
     */
    public function telemetry(Device $device)
    {
        try {
            $telemetry = $this->deviceService->getDeviceTelemetry($device->flespi_device_id, [], false);
            return response()->json($telemetry);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get device messages (API endpoint)
     */
    public function messages(Device $device, Request $request)
    {
        try {
            $from = $request->input('from');
            $to = $request->input('to');
            $limit = $request->input('limit', 100);

            $messages = $this->deviceService->getDeviceMessages(
                $device->flespi_device_id,
                $from ? strtotime($from) : null,
                $to ? strtotime($to) : null,
                $limit
            );

            return response()->json($messages);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Assign driver to device
     */
    public function assignDriver(Request $request, Device $device): RedirectResponse
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
        ]);

        try {
            $driver = Driver::findOrFail($validated['driver_id']);

            // Check if driver is active
            if (!$driver->is_active) {
                return redirect()->back()
                    ->with('error', 'Cannot assign inactive driver');
            }

            // End any current assignment for this device
            if ($device->current_driver_id) {
                DriverAssignment::where('device_id', $device->id)
                    ->whereNull('end_time')
                    ->update(['end_time' => now()]);
            }

            // Create new assignment
            DriverAssignment::create([
                'driver_id' => $driver->id,
                'device_id' => $device->id,
                'start_time' => now(),
            ]);

            // Update device's current driver
            $device->update(['current_driver_id' => $driver->id]);

            return redirect()->back()
                ->with('success', "Driver {$driver->name} assigned to {$device->name}");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to assign driver: ' . $e->getMessage());
        }
    }

    /**
     * Unassign current driver from device
     */
    public function unassignDriver(Device $device): RedirectResponse
    {
        try {
            if (!$device->current_driver_id) {
                return redirect()->back()
                    ->with('error', 'No driver currently assigned to this device');
            }

            // End current assignment
            DriverAssignment::where('device_id', $device->id)
                ->whereNull('end_time')
                ->update(['end_time' => now()]);

            // Clear device's current driver
            $device->update(['current_driver_id' => null]);

            return redirect()->back()
                ->with('success', 'Driver unassigned from device');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to unassign driver: ' . $e->getMessage());
        }
    }
}
