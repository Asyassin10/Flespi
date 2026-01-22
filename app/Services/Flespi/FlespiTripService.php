<?php

declare(strict_types=1);

namespace App\Services\Flespi;

use Illuminate\Support\Collection;

/**
 * Flespi Trip Service
 *
 * Handles trip/interval operations using Flespi Calculators
 * - Get trips (intervals) for devices
 * - Get specific trip details
 * - Configure trip detection calculator
 */
class FlespiTripService extends FlespiApiService
{
    /**
     * Get all calculators
     *
     * @return Collection Collection of calculators
     */
    public function getAllCalculators(): Collection
    {
        $calculators = $this->get('/gw/calcs/all');
        return collect($calculators);
    }

    /**
     * Get specific calculator by ID
     *
     * @param int $calcId Calculator ID
     * @return array|null Calculator data
     */
    public function getCalculator(int $calcId): ?array
    {
        $calculators = $this->get('/gw/calcs/' . $calcId);
        return $calculators[0] ?? null;
    }

    /**
     * Create a trip detection calculator
     *
     * @param string $name Calculator name
     * @param array $config Additional configuration
     * @return array Created calculator
     */
    public function createTripCalculator(string $name = 'trips_detector', array $config = []): array
    {
        $defaultConfig = [
            'name' => $name,
            'type' => 'intervals',
            'selectors' => [
                [
                    'type' => 'expression',
                    'expression' => 'position.speed > 5', // Trip starts when speed > 5 km/h
                ]
            ],
            'counters' => [
                [
                    'type' => 'mileage',
                    'name' => 'distance',
                ],
                [
                    'type' => 'duration',
                    'name' => 'duration',
                ],
                [
                    'type' => 'expression',
                    'name' => 'max_speed',
                    'expression' => 'max(position.speed)',
                ],
                [
                    'type' => 'expression',
                    'name' => 'avg_speed',
                    'expression' => 'avg(position.speed)',
                ],
            ],
        ];

        $calculatorData = array_merge($defaultConfig, $config);
        $calculators = $this->post('/gw/calcs', [$calculatorData]);

        return $calculators[0] ?? [];
    }

    /**
     * Assign devices to calculator
     *
     * @param int $calcId Calculator ID
     * @param array $deviceIds Array of device IDs
     * @return array Result
     */
    public function assignDevicesToCalculator(int $calcId, array $deviceIds): array
    {
        return $this->post('/gw/calcs/' . $calcId . '/devices', [
            'devices' => $deviceIds
        ]);
    }

    /**
     * Get intervals (trips) for a device
     *
     * @param int $calcId Calculator ID
     * @param int $deviceId Device ID
     * @param int|null $from Start timestamp (Unix timestamp)
     * @param int|null $to End timestamp (Unix timestamp)
     * @param int $limit Maximum number of intervals
     * @return Collection Collection of intervals
     */
    public function getDeviceIntervals(
        int $calcId,
        int $deviceId,
        ?int $from = null,
        ?int $to = null,
        int $limit = 100
    ): Collection {
        $params = [];

        if ($from) {
            $params['begin.from'] = $from;
        }
        if ($to) {
            $params['begin.to'] = $to;
        }

        $params['limit'] = $limit;

        $endpoint = '/gw/calcs/' . $calcId . '/devices/' . $deviceId . '/intervals/all';
        $intervals = $this->get($endpoint, $params, false);

        return collect($intervals)->map(function ($interval) {
            return $this->formatInterval($interval);
        });
    }

    /**
     * Get last interval (trip) for device
     *
     * @param int $calcId Calculator ID
     * @param int $deviceId Device ID
     * @return array|null Last interval data
     */
    public function getLastInterval(int $calcId, int $deviceId): ?array
    {
        $endpoint = '/gw/calcs/' . $calcId . '/devices/' . $deviceId . '/intervals/last';
        $intervals = $this->get($endpoint, [], false);
        $interval = $intervals[0] ?? null;

        return $interval ? $this->formatInterval($interval) : null;
    }

    /**
     * Get specific interval details
     *
     * @param int $calcId Calculator ID
     * @param int $deviceId Device ID
     * @param int $intervalId Interval ID
     * @return array|null Interval data
     */
    public function getInterval(int $calcId, int $deviceId, int $intervalId): ?array
    {
        $endpoint = '/gw/calcs/' . $calcId . '/devices/' . $deviceId . '/intervals/' . $intervalId;
        $intervals = $this->get($endpoint, [], false);
        $interval = $intervals[0] ?? null;

        return $interval ? $this->formatInterval($interval) : null;
    }

    /**
     * Get interval messages (route points)
     *
     * @param int $calcId Calculator ID
     * @param int $deviceId Device ID
     * @param int $intervalId Interval ID
     * @return Collection Collection of messages with position data
     */
    public function getIntervalMessages(int $calcId, int $deviceId, int $intervalId): Collection
    {
        $endpoint = '/gw/calcs/' . $calcId . '/devices/' . $deviceId . '/intervals/' . $intervalId . '/messages/all';
        $messages = $this->get($endpoint, [], false);

        return collect($messages)->map(function ($message) {
            return [
                'latitude' => $message['position.latitude'] ?? null,
                'longitude' => $message['position.longitude'] ?? null,
                'speed' => $message['position.speed'] ?? null,
                'timestamp' => $message['timestamp'] ?? null,
            ];
        })->filter(function ($point) {
            return $point['latitude'] !== null && $point['longitude'] !== null;
        });
    }

    /**
     * Get trips for multiple devices in a date range
     *
     * @param int $calcId Calculator ID
     * @param array $deviceIds Array of device IDs
     * @param int $from Start timestamp
     * @param int $to End timestamp
     * @return Collection Collection of trips with device info
     */
    public function getTripsForDevices(int $calcId, array $deviceIds, int $from, int $to): Collection
    {
        $trips = collect();

        foreach ($deviceIds as $deviceId) {
            $intervals = $this->getDeviceIntervals($calcId, $deviceId, $from, $to);

            $intervals->each(function ($interval) use ($deviceId, &$trips) {
                $interval['device_id'] = $deviceId;
                $trips->push($interval);
            });
        }

        return $trips->sortByDesc('begin');
    }

    /**
     * Format interval data for consistent structure
     *
     * @param array $interval Raw interval data from Flespi
     * @return array Formatted interval
     */
    protected function formatInterval(array $interval): array
    {
        // Helper to extract values from various possible locations
        $getValue = function($key) use ($interval) {
            // Check multiple possible locations for the value
            return $interval[$key]
                ?? $interval['counters'][$key]
                ?? $interval['properties'][$key]
                ?? $interval['value'][$key]
                ?? null;
        };

        // Extract distance - Flespi returns in kilometers already
        $distance = $getValue('distance')
            ?? $getValue('mileage')
            ?? $getValue('odometer')
            ?? 0;

        // Extract speeds - note Flespi uses dots in field names
        $maxSpeed = $getValue('max.speed')
            ?? $getValue('max_speed')
            ?? $getValue('speed.max')
            ?? 0;

        $avgSpeed = $getValue('avg.speed')
            ?? $getValue('avg_speed')
            ?? $getValue('average_speed')
            ?? $getValue('speed.avg')
            ?? 0;

        return [
            'id' => $interval['id'] ?? null,
            'begin' => $interval['begin'] ?? null,
            'end' => $interval['end'] ?? null,
            'duration' => isset($interval['end'], $interval['begin'])
                ? $interval['end'] - $interval['begin']
                : ($interval['duration'] ?? 0),
            'distance' => $distance, // Already in kilometers from Flespi
            'max_speed' => $maxSpeed,
            'avg_speed' => $avgSpeed,
            'route' => $interval['route'] ?? null, // Encoded polyline
            'start_location' => [
                'latitude' => $interval['begin.position.latitude']
                    ?? $interval['position.begin.latitude']
                    ?? $interval['start_latitude']
                    ?? null,
                'longitude' => $interval['begin.position.longitude']
                    ?? $interval['position.begin.longitude']
                    ?? $interval['start_longitude']
                    ?? null,
            ],
            'end_location' => [
                'latitude' => $interval['end.position.latitude']
                    ?? $interval['position.end.latitude']
                    ?? $interval['end_latitude']
                    ?? null,
                'longitude' => $interval['end.position.longitude']
                    ?? $interval['position.end.longitude']
                    ?? $interval['end_longitude']
                    ?? null,
            ],
            'metadata' => $interval,
        ];
    }

    /**
     * Delete calculator
     *
     * @param int $calcId Calculator ID
     * @return array Result
     */
    public function deleteCalculator(int $calcId): array
    {
        return $this->delete('/gw/calcs/' . $calcId);
    }

    /**
     * Update calculator configuration
     *
     * @param int $calcId Calculator ID
     * @param array $updates Configuration updates
     * @return array Updated calculator
     */
    public function updateCalculator(int $calcId, array $updates): array
    {
        $calculators = $this->put('/gw/calcs/' . $calcId, [$updates]);
        return $calculators[0] ?? [];
    }

    /**
     * Get trip statistics for a device in date range
     *
     * @param int $calcId Calculator ID
     * @param int $deviceId Device ID
     * @param int $from Start timestamp
     * @param int $to End timestamp
     * @return array Statistics (total_trips, total_distance, total_duration, avg_speed)
     */
    public function getTripStatistics(int $calcId, int $deviceId, int $from, int $to): array
    {
        $intervals = $this->getDeviceIntervals($calcId, $deviceId, $from, $to, 1000);

        return [
            'total_trips' => $intervals->count(),
            'total_distance' => $intervals->sum('distance'),
            'total_duration' => $intervals->sum('duration'),
            'avg_speed' => $intervals->avg('avg_speed'),
            'max_speed' => $intervals->max('max_speed'),
        ];
    }
}
