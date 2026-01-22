<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\Flespi\FlespiDeviceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
}
