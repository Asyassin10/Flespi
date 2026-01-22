<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverController extends Controller
{
    /**
     * Display a listing of drivers
     */
    public function index(): View
    {
        $drivers = Driver::with('currentDevices')
            ->orderBy('name')
            ->paginate(20);

        return view('drivers.index', compact('drivers'));
    }

    /**
     * Show the form for creating a new driver
     */
    public function create(): View
    {
        return view('drivers.create');
    }

    /**
     * Store a newly created driver
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'license_number' => 'nullable|string|max:100',
            'rfid_card' => 'nullable|string|max:100|unique:drivers',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        $driver = Driver::create($validated);

        return redirect()->route('drivers.show', $driver)
            ->with('success', 'Driver created successfully!');
    }

    /**
     * Display the specified driver
     */
    public function show(Driver $driver): View
    {
        $driver->load([
            'currentDevices',
            'trips' => function($query) {
                $query->orderBy('start_time', 'desc')->limit(10);
            },
            'assignments' => function($query) {
                $query->orderBy('start_time', 'desc')->limit(10);
            }
        ]);

        return view('drivers.show', compact('driver'));
    }

    /**
     * Show the form for editing the driver
     */
    public function edit(Driver $driver): View
    {
        return view('drivers.edit', compact('driver'));
    }

    /**
     * Update the driver
     */
    public function update(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'license_number' => 'nullable|string|max:100',
            'rfid_card' => 'nullable|string|max:100|unique:drivers,rfid_card,' . $driver->id,
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $driver->update($validated);

        return redirect()->route('drivers.show', $driver)
            ->with('success', 'Driver updated successfully!');
    }

    /**
     * Remove the driver
     */
    public function destroy(Driver $driver)
    {
        $driver->delete();

        return redirect()->route('drivers.index')
            ->with('success', 'Driver deleted successfully!');
    }
}
