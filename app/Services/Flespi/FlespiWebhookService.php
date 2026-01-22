<?php

declare(strict_types=1);

namespace App\Services\Flespi;

use App\Models\Device;
use App\Models\Trip;
use Illuminate\Support\Facades\Log;

/**
 * Flespi Webhook Service
 *
 * Handles incoming webhooks from Flespi
 * - Process device messages
 * - Process trip updates
 * - Update local database with Flespi data
 */
class FlespiWebhookService extends FlespiApiService
{
    /**
     * Process incoming webhook payload
     *
     * @param array $payload Webhook payload from Flespi
     * @return array Processing result
     */
    public function processWebhook(array $payload): array
    {
        Log::info('Processing Flespi webhook', ['payload' => $payload]);

        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            // Determine webhook type and process accordingly
            if (isset($payload['messages'])) {
                return $this->processDeviceMessages($payload);
            } elseif (isset($payload['intervals'])) {
                return $this->processTripIntervals($payload);
            } elseif (isset($payload['events'])) {
                return $this->processGeofenceEvents($payload);
            } else {
                Log::warning('Unknown webhook payload type', ['payload' => $payload]);
                $results['errors'][] = 'Unknown payload type';
            }

        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $results['failed']++;
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Process device messages from webhook
     *
     * @param array $payload Webhook payload
     * @return array Processing result
     */
    protected function processDeviceMessages(array $payload): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $messages = $payload['messages'] ?? [];

        foreach ($messages as $message) {
            try {
                $deviceId = $message['device.id'] ?? null;

                if (!$deviceId) {
                    continue;
                }

                // Find local device
                $device = Device::where('flespi_device_id', $deviceId)->first();

                if (!$device) {
                    Log::warning('Device not found for webhook message', ['device_id' => $deviceId]);
                    continue;
                }

                // Update device with latest data
                $this->updateDeviceFromMessage($device, $message);

                $results['processed']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = $e->getMessage();
                Log::error('Error processing device message', [
                    'message' => $message,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Update device with message data
     *
     * @param Device $device Local device model
     * @param array $message Message data
     */
    protected function updateDeviceFromMessage(Device $device, array $message): void
    {
        $updateData = [];

        // Update location if available
        if (isset($message['position.latitude']) && isset($message['position.longitude'])) {
            $updateData['last_latitude'] = $message['position.latitude'];
            $updateData['last_longitude'] = $message['position.longitude'];
        }

        // Update speed
        if (isset($message['position.speed'])) {
            $updateData['last_speed'] = $message['position.speed'];
        }

        // Update timestamp
        if (isset($message['timestamp'])) {
            $updateData['last_message_at'] = date('Y-m-d H:i:s', $message['timestamp']);

            // Determine online/offline status (online if message is recent)
            $fiveMinutesAgo = now()->subMinutes(5)->timestamp;
            $updateData['status'] = $message['timestamp'] >= $fiveMinutesAgo ? 'online' : 'offline';
        }

        // Store full telemetry
        $updateData['telemetry'] = $message;

        $device->update($updateData);

        Log::debug('Device updated from webhook', [
            'device_id' => $device->id,
            'flespi_device_id' => $device->flespi_device_id,
        ]);
    }

    /**
     * Process trip intervals from webhook
     *
     * @param array $payload Webhook payload
     * @return array Processing result
     */
    protected function processTripIntervals(array $payload): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $intervals = $payload['intervals'] ?? [];

        foreach ($intervals as $interval) {
            try {
                $deviceId = $interval['device.id'] ?? null;
                $intervalId = $interval['id'] ?? null;

                if (!$deviceId || !$intervalId) {
                    continue;
                }

                // Find local device
                $device = Device::where('flespi_device_id', $deviceId)->first();

                if (!$device) {
                    Log::warning('Device not found for trip webhook', ['device_id' => $deviceId]);
                    continue;
                }

                // Create or update trip
                $this->createOrUpdateTrip($device, $interval);

                $results['processed']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = $e->getMessage();
                Log::error('Error processing trip interval', [
                    'interval' => $interval,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Create or update trip from interval data
     *
     * @param Device $device Local device model
     * @param array $interval Interval data
     */
    protected function createOrUpdateTrip(Device $device, array $interval): void
    {
        $tripData = [
            'flespi_interval_id' => $interval['id'],
            'device_id' => $device->id,
            'driver_id' => $device->current_driver_id,
            'start_time' => date('Y-m-d H:i:s', $interval['begin']),
            'end_time' => date('Y-m-d H:i:s', $interval['end']),
            'duration' => $interval['end'] - $interval['begin'],
            'distance' => $interval['counters']['distance'] ?? 0,
            'avg_speed' => $interval['counters']['avg_speed'] ?? null,
            'max_speed' => $interval['counters']['max_speed'] ?? null,
            'start_latitude' => $interval['begin.position.latitude'] ?? null,
            'start_longitude' => $interval['begin.position.longitude'] ?? null,
            'end_latitude' => $interval['end.position.latitude'] ?? null,
            'end_longitude' => $interval['end.position.longitude'] ?? null,
            'metadata' => $interval,
        ];

        Trip::updateOrCreate(
            ['flespi_interval_id' => $interval['id']],
            $tripData
        );

        Log::debug('Trip created/updated from webhook', [
            'trip_id' => $interval['id'],
            'device_id' => $device->id,
        ]);
    }

    /**
     * Process geofence events from webhook
     *
     * @param array $payload Webhook payload
     * @return array Processing result
     */
    protected function processGeofenceEvents(array $payload): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $events = $payload['events'] ?? [];

        foreach ($events as $event) {
            try {
                // Log geofence entry/exit events
                Log::info('Geofence event received', [
                    'device_id' => $event['device.id'] ?? null,
                    'geofence_id' => $event['geofence.id'] ?? null,
                    'event_type' => $event['type'] ?? null, // enter or exit
                    'timestamp' => $event['timestamp'] ?? null,
                ]);

                // You can implement custom logic here
                // For example: send notifications, update device status, etc.

                $results['processed']++;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = $e->getMessage();
                Log::error('Error processing geofence event', [
                    'event' => $event,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Create HTTP stream in Flespi to send data to Laravel webhook
     *
     * @param string $webhookUrl Laravel webhook URL
     * @param string $name Stream name
     * @return array Created stream
     */
    public function createWebhookStream(string $webhookUrl, string $name = 'laravel_webhook'): array
    {
        $streamData = [
            'name' => $name,
            'protocol_id' => 'http',
            'configuration' => [
                'uri' => $webhookUrl,
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ],
        ];

        $streams = $this->post('/gw/streams', [$streamData]);
        return $streams[0] ?? [];
    }

    /**
     * Assign devices to webhook stream
     *
     * @param int $streamId Stream ID
     * @param array $deviceIds Array of device IDs
     * @return array Result
     */
    public function assignDevicesToStream(int $streamId, array $deviceIds): array
    {
        return $this->post('/gw/streams/' . $streamId . '/devices', [
            'devices' => $deviceIds
        ]);
    }

    /**
     * Get all streams
     *
     * @return array Streams
     */
    public function getAllStreams(): array
    {
        return $this->get('/gw/streams/all');
    }

    /**
     * Delete stream
     *
     * @param int $streamId Stream ID
     * @return array Result
     */
    public function deleteStream(int $streamId): array
    {
        return $this->delete('/gw/streams/' . $streamId);
    }
}
