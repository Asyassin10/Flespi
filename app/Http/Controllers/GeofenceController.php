<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Geofence;
use App\Services\Flespi\FlespiGeofenceService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class GeofenceController extends Controller
{
    public function __construct(
        private FlespiGeofenceService $geofenceService
    ) {}

    /**
     * Display list of geofences
     */
    public function index(): View
    {
        $geofences = Geofence::orderBy('name')->get();

        return view('geofences.index', compact('geofences'));
    }

    /**
     * Show form for creating new geofence
     */
    public function create(): View
    {
        return view('geofences.create');
    }

    /**
     * Store newly created geofence
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:circle,polygon',
            'color' => 'nullable|string|max:20',
            'geometry' => 'required|json',
        ]);

        try {
            $geometry = json_decode($validated['geometry'], true);

            // Create geofence in Flespi
            $flespiGeofence = $this->geofenceService->createGeofence(
                $validated['name'],
                $geometry
            );

            // Store in local database
            Geofence::create([
                'flespi_geofence_id' => $flespiGeofence['id'],
                'name' => $validated['name'],
                'type' => $validated['type'],
                'geometry' => $geometry,
                'color' => $validated['color'] ?? '#3B82F6',
            ]);

            return redirect()->route('geofences.index')
                ->with('success', 'Geofence created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create geofence: ' . $e->getMessage());
        }
    }

    /**
     * Display specific geofence
     */
    public function show(Geofence $geofence): View
    {
        return view('geofences.show', compact('geofence'));
    }

    /**
     * Show form for editing geofence
     */
    public function edit(Geofence $geofence): View
    {
        return view('geofences.edit', compact('geofence'));
    }

    /**
     * Update geofence
     */
    public function update(Request $request, Geofence $geofence): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
            'geometry' => 'nullable|json',
        ]);

        try {
            $updates = ['name' => $validated['name']];

            if (isset($validated['geometry'])) {
                $geometry = json_decode($validated['geometry'], true);
                $updates['geometry'] = $geometry;
            }

            // Update in Flespi
            if ($geofence->flespi_geofence_id) {
                $this->geofenceService->updateGeofence(
                    $geofence->flespi_geofence_id,
                    $updates
                );
            }

            // Update locally
            $geofence->update([
                'name' => $validated['name'],
                'color' => $validated['color'] ?? $geofence->color,
                'geometry' => $updates['geometry'] ?? $geofence->geometry,
            ]);

            return redirect()->route('geofences.show', $geofence)
                ->with('success', 'Geofence updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update geofence: ' . $e->getMessage());
        }
    }

    /**
     * Delete geofence
     */
    public function destroy(Geofence $geofence): RedirectResponse
    {
        try {
            // Delete from Flespi
            if ($geofence->flespi_geofence_id) {
                $this->geofenceService->deleteGeofence($geofence->flespi_geofence_id);
            }

            // Delete locally
            $geofence->delete();

            return redirect()->route('geofences.index')
                ->with('success', 'Geofence deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete geofence: ' . $e->getMessage());
        }
    }

    /**
     * Sync geofences from Flespi
     */
    public function sync(): RedirectResponse
    {
        try {
            $flespiGeofences = $this->geofenceService->getAllGeofences(false);
            $synced = 0;

            foreach ($flespiGeofences as $flespiGeofence) {
                $geometry = $flespiGeofence['geometry'] ?? [];
                $type = $geometry['type'] ?? 'circle';

                Geofence::updateOrCreate(
                    ['flespi_geofence_id' => $flespiGeofence['id']],
                    [
                        'name' => $flespiGeofence['name'] ?? 'Unnamed Geofence',
                        'type' => $type,
                        'geometry' => $geometry,
                        'color' => $flespiGeofence['color'] ?? '#3B82F6',
                    ]
                );

                $synced++;
            }

            return redirect()->route('geofences.index')
                ->with('success', "Synced {$synced} geofences from Flespi!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to sync geofences: ' . $e->getMessage());
        }
    }

    /**
     * Perform hit-test to check if point is within geofences
     */
    public function hitTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $geofences = $this->geofenceService->hitTest(
                $validated['latitude'],
                $validated['longitude']
            );

            return response()->json([
                'success' => true,
                'geofences' => $geofences,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
