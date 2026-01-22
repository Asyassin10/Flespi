<?php

declare(strict_types=1);

namespace App\Services\Flespi;

use Illuminate\Support\Collection;

/**
 * Flespi Device Service
 *
 * Handles all device-related operations with Flespi API
 * - List devices
 * - Get device details
 * - Get device telemetry (last known values)
 * - Get device messages (historical data)
 * - Create/Update/Delete devices
 */
class FlespiDeviceService extends FlespiApiService
{
    /**
     * Get all devices from Flespi
     *
     * @param bool $useCache Whether to use caching
     * @return Collection Collection of devices
     */
    public function getAllDevices(bool $useCache = true): Collection
    {
        $devices = $this->get('/gw/devices/all', [], $useCache);
        return collect($devices);
    }

    /**
     * Get specific device by ID
     *
     * @param int $deviceId Flespi device ID
     * @param bool $useCache Whether to use caching
     * @return array|null Device data
     */
    public function getDevice(int $deviceId, bool $useCache = true): ?array
    {
        $devices = $this->get('/gw/devices/' . $deviceId, [], $useCache);
        return $devices[0] ?? null;
    }

    /**
     * Get device telemetry (last known values)
     *
     * @param int $deviceId Flespi device ID
     * @param array $fields Specific fields to retrieve (e.g., ['position.latitude', 'position.longitude'])
     * @param bool $useCache Whether to use caching
     * @return array Telemetry data
     */
    public function getDeviceTelemetry(int $deviceId, array $fields = [], bool $useCache = true): array
    {
        $endpoint = '/gw/devices/' . $deviceId . '/telemetry';

        if (!empty($fields)) {
            $endpoint .= '/' . implode(',', $fields);
        }

        $telemetry = $this->get($endpoint, [], $useCache);
        return $telemetry[0] ?? [];
    }

    /**
     * Get device location (latitude, longitude, speed)
     *
     * @param int $deviceId Flespi device ID
     * @param bool $useCache Whether to use caching
     * @return array Location data with keys: latitude, longitude, speed, timestamp
     */
    public function getDeviceLocation(int $deviceId, bool $useCache = true): array
    {
        $fields = [
            'position.latitude',
            'position.longitude',
            'position.speed',
            'timestamp',
        ];

        $telemetry = $this->getDeviceTelemetry($deviceId, $fields, $useCache);
        $data = $telemetry['telemetry'] ?? $telemetry;

        // Helper function to extract value
        $extractValue = function ($field) use ($data) {
            if (!isset($data[$field])) {
                return null;
            }
            return is_array($data[$field]) ? ($data[$field]['value'] ?? null) : $data[$field];
        };

        // Extract timestamp
        $timestamp = null;
        if (isset($data['timestamp'])) {
            $timestamp = is_array($data['timestamp']) ? ($data['timestamp']['value'] ?? null) : $data['timestamp'];
        }

        return [
            'latitude' => $extractValue('position.latitude'),
            'longitude' => $extractValue('position.longitude'),
            'speed' => $extractValue('position.speed'),
            'timestamp' => $timestamp,
        ];
    }

    /**
     * Get device messages (historical data)
     *
     * @param int $deviceId Flespi device ID
     * @param int|null $from Start timestamp (Unix timestamp)
     * @param int|null $to End timestamp (Unix timestamp)
     * @param int $limit Maximum number of messages to retrieve
     * @return Collection Collection of messages
     */
    public function getDeviceMessages(
        int $deviceId,
        ?int $from = null,
        ?int $to = null,
        int $limit = 100
    ): Collection {
        $params = [];

        if ($from || $to) {
            $data = ['begin' => []];
            if ($from) {
                $data['begin']['from'] = $from;
            }
            if ($to) {
                $data['begin']['to'] = $to;
            }
            $params['data'] = json_encode($data);
        }

        $params['limit'] = $limit;

        $messages = $this->get('/gw/devices/' . $deviceId . '/messages', $params, false);
        return collect($messages);
    }

    /**
     * Get latest message for device
     *
     * @param int $deviceId Flespi device ID
     * @return array|null Latest message
     */
    public function getLatestMessage(int $deviceId): ?array
    {
        $messages = $this->getDeviceMessages($deviceId, null, null, 1);
        return $messages->first();
    }

    /**
     * Create a new device in Flespi
     *
     * @param array $deviceData Device configuration
     * @return array Created device data
     */
    public function createDevice(array $deviceData): array
    {
        $devices = $this->post('/gw/devices', [$deviceData]);
        $this->clearCache('/gw/devices/all');
        return $devices[0] ?? [];
    }

    /**
     * Update device in Flespi
     *
     * @param int $deviceId Flespi device ID
     * @param array $updates Device data to update
     * @return array Updated device data
     */
    public function updateDevice(int $deviceId, array $updates): array
    {
        $devices = $this->put('/gw/devices/' . $deviceId, [$updates]);
        $this->clearCache('/gw/devices/all');
        $this->clearCache('/gw/devices/' . $deviceId);
        return $devices[0] ?? [];
    }

    /**
     * Delete device from Flespi
     *
     * @param int $deviceId Flespi device ID
     * @return array Deletion result
     */
    public function deleteDevice(int $deviceId): array
    {
        $result = $this->delete('/gw/devices/' . $deviceId);
        $this->clearCache('/gw/devices/all');
        $this->clearCache('/gw/devices/' . $deviceId);
        return $result;
    }

    /**
     * Check if device is online
     * Device is considered online if it sent a message in the last 5 minutes
     *
     * @param int $deviceId Flespi device ID
     * @return bool True if online
     */
    public function isDeviceOnline(int $deviceId): bool
    {
        $telemetry = $this->getDeviceTelemetry($deviceId, ['timestamp'], true);
        $timestamp = $telemetry['telemetry']['timestamp'] ?? null;

        if (!$timestamp) {
            return false;
        }

        // Device is online if last message was within 5 minutes
        $fiveMinutesAgo = now()->subMinutes(5)->timestamp;
        return $timestamp >= $fiveMinutesAgo;
    }

    /**
     * Get multiple devices by IDs
     *
     * @param array $deviceIds Array of Flespi device IDs
     * @return Collection Collection of devices
     */
    public function getDevices(array $deviceIds): Collection
    {
        $devices = collect();

        foreach ($deviceIds as $deviceId) {
            $device = $this->getDevice($deviceId);
            if ($device) {
                $devices->push($device);
            }
        }

        return $devices;
    }

    /**
     * Get devices with their current locations
     *
     * @return Collection Collection with device_id, name, latitude, longitude, speed, timestamp
     */
    public function getDevicesWithLocations(): Collection
    {
        $devices = $this->getAllDevices();

        return $devices->map(function ($device) {
            $location = $this->getDeviceLocation($device['id'], true);

            return [
                'id' => $device['id'],
                'name' => $device['name'] ?? 'Unknown',
                'ident' => $device['configuration']['ident'] ?? null,
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'speed' => $location['speed'],
                'timestamp' => $location['timestamp'],
                'is_online' => $location['timestamp'] ?
                    ($location['timestamp'] >= now()->subMinutes(5)->timestamp) : false,
            ];
        })->filter(function ($device) {
            // Only include devices with valid location
            return $device['latitude'] !== null && $device['longitude'] !== null;
        });
    }
}
