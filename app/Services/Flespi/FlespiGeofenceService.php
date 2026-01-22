<?php

declare(strict_types=1);

namespace App\Services\Flespi;

use Illuminate\Support\Collection;

/**
 * Flespi Geofence Service
 *
 * Handles geofence operations with Flespi API
 * - Create, update, delete geofences
 * - List geofences
 * - Assign geofences to calculators for entry/exit detection
 */
class FlespiGeofenceService extends FlespiApiService
{
    /**
     * Get all geofences from Flespi
     *
     * @param bool $useCache Whether to use caching
     * @return Collection Collection of geofences
     */
    public function getAllGeofences(bool $useCache = true): Collection
    {
        $geofences = $this->get('/gw/geofences/all', [], $useCache);
        return collect($geofences);
    }

    /**
     * Get specific geofence by ID
     *
     * @param int $geofenceId Flespi geofence ID
     * @param bool $useCache Whether to use caching
     * @return array|null Geofence data
     */
    public function getGeofence(int $geofenceId, bool $useCache = true): ?array
    {
        $geofences = $this->get('/gw/geofences/' . $geofenceId, [], $useCache);
        return $geofences[0] ?? null;
    }

    /**
     * Create a circular geofence
     *
     * @param string $name Geofence name
     * @param float $latitude Center latitude
     * @param float $longitude Center longitude
     * @param float $radius Radius in meters
     * @return array Created geofence
     */
    public function createCircleGeofence(
        string $name,
        float $latitude,
        float $longitude,
        float $radius
    ): array {
        $geofenceData = [
            'name' => $name,
            'geometry' => [
                'type' => 'circle',
                'center' => [
                    'lat' => $latitude,
                    'lon' => $longitude,
                ],
                'radius' => $radius,
            ],
        ];

        $geofences = $this->post('/gw/geofences', [$geofenceData]);
        $this->clearCache('/gw/geofences/all');

        return $geofences[0] ?? [];
    }

    /**
     * Create a polygon geofence
     *
     * @param string $name Geofence name
     * @param array $coordinates Array of [lat, lon] coordinates
     * @return array Created geofence
     */
    public function createPolygonGeofence(string $name, array $coordinates): array
    {
        // Format coordinates for Flespi: [[lon, lat], [lon, lat], ...]
        $formattedCoordinates = array_map(function ($coord) {
            return [$coord['lon'] ?? $coord[1], $coord['lat'] ?? $coord[0]];
        }, $coordinates);

        $geofenceData = [
            'name' => $name,
            'geometry' => [
                'type' => 'polygon',
                'coordinates' => [$formattedCoordinates],
            ],
        ];

        $geofences = $this->post('/gw/geofences', [$geofenceData]);
        $this->clearCache('/gw/geofences/all');

        return $geofences[0] ?? [];
    }

    /**
     * Update geofence
     *
     * @param int $geofenceId Flespi geofence ID
     * @param array $updates Data to update
     * @return array Updated geofence
     */
    public function updateGeofence(int $geofenceId, array $updates): array
    {
        $geofences = $this->put('/gw/geofences/' . $geofenceId, [$updates]);
        $this->clearCache('/gw/geofences/all');
        $this->clearCache('/gw/geofences/' . $geofenceId);

        return $geofences[0] ?? [];
    }

    /**
     * Delete geofence
     *
     * @param int $geofenceId Flespi geofence ID
     * @return array Deletion result
     */
    public function deleteGeofence(int $geofenceId): array
    {
        $result = $this->delete('/gw/geofences/' . $geofenceId);
        $this->clearCache('/gw/geofences/all');
        $this->clearCache('/gw/geofences/' . $geofenceId);

        return $result;
    }

    /**
     * Assign geofence to calculator for entry/exit detection
     *
     * @param int $geofenceId Geofence ID
     * @param int $calcId Calculator ID
     * @return array Result
     */
    public function assignGeofenceToCalculator(int $geofenceId, int $calcId): array
    {
        return $this->post('/gw/geofences/' . $geofenceId . '/calcs', [
            'calcs' => [$calcId]
        ]);
    }

    /**
     * Check if a point is inside a circular geofence
     *
     * @param float $pointLat Point latitude
     * @param float $pointLon Point longitude
     * @param float $centerLat Circle center latitude
     * @param float $centerLon Circle center longitude
     * @param float $radius Radius in meters
     * @return bool True if point is inside
     */
    public function isPointInCircle(
        float $pointLat,
        float $pointLon,
        float $centerLat,
        float $centerLon,
        float $radius
    ): bool {
        $earthRadius = 6371000; // Earth radius in meters

        $dLat = deg2rad($pointLat - $centerLat);
        $dLon = deg2rad($pointLon - $centerLon);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($centerLat)) * cos(deg2rad($pointLat)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance <= $radius;
    }

    /**
     * Check if a point is inside a polygon geofence (Ray casting algorithm)
     *
     * @param float $pointLat Point latitude
     * @param float $pointLon Point longitude
     * @param array $polygon Array of [lat, lon] coordinates
     * @return bool True if point is inside
     */
    public function isPointInPolygon(float $pointLat, float $pointLon, array $polygon): bool
    {
        $intersections = 0;
        $vertices = count($polygon);

        for ($i = 0; $i < $vertices; $i++) {
            $j = ($i + 1) % $vertices;

            $lat1 = $polygon[$i][0] ?? $polygon[$i]['lat'];
            $lon1 = $polygon[$i][1] ?? $polygon[$i]['lon'];
            $lat2 = $polygon[$j][0] ?? $polygon[$j]['lat'];
            $lon2 = $polygon[$j][1] ?? $polygon[$j]['lon'];

            if ((($lon1 > $pointLon) !== ($lon2 > $pointLon)) &&
                ($pointLat < ($lat2 - $lat1) * ($pointLon - $lon1) / ($lon2 - $lon1) + $lat1)) {
                $intersections++;
            }
        }

        return ($intersections % 2) !== 0;
    }

    /**
     * Get geofences containing a specific point
     *
     * @param float $latitude Point latitude
     * @param float $longitude Point longitude
     * @return Collection Collection of geofences containing the point
     */
    public function getGeofencesContainingPoint(float $latitude, float $longitude): Collection
    {
        $geofences = $this->getAllGeofences();

        return $geofences->filter(function ($geofence) use ($latitude, $longitude) {
            $geometry = $geofence['geometry'] ?? [];

            if ($geometry['type'] === 'circle') {
                $center = $geometry['center'];
                $radius = $geometry['radius'];

                return $this->isPointInCircle(
                    $latitude,
                    $longitude,
                    $center['lat'],
                    $center['lon'],
                    $radius
                );
            } elseif ($geometry['type'] === 'polygon') {
                $coordinates = $geometry['coordinates'][0] ?? [];

                // Convert [lon, lat] to [lat, lon]
                $polygon = array_map(function ($coord) {
                    return [$coord[1], $coord[0]];
                }, $coordinates);

                return $this->isPointInPolygon($latitude, $longitude, $polygon);
            }

            return false;
        });
    }

    /**
     * Get multiple geofences by IDs
     *
     * @param array $geofenceIds Array of geofence IDs
     * @return Collection Collection of geofences
     */
    public function getGeofences(array $geofenceIds): Collection
    {
        $geofences = collect();

        foreach ($geofenceIds as $geofenceId) {
            $geofence = $this->getGeofence($geofenceId);
            if ($geofence) {
                $geofences->push($geofence);
            }
        }

        return $geofences;
    }

    /**
     * Create geofence from array data
     * Automatically detects circle vs polygon based on geometry structure
     *
     * @param string $name Geofence name
     * @param array $geometry Geometry data
     * @return array Created geofence
     */
    public function createGeofence(string $name, array $geometry): array
    {
        $geofenceData = [
            'name' => $name,
            'geometry' => $geometry,
        ];

        $geofences = $this->post('/gw/geofences', [$geofenceData]);
        $this->clearCache('/gw/geofences/all');

        return $geofences[0] ?? [];
    }
}
