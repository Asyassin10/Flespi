<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Driver;
use App\Models\DriverAssignment;
use App\Models\Geofence;
use App\Models\Trip;
use Illuminate\Database\Seeder;

class FlespiDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating demo data for Flespi Fleet Management...');

        // Create Drivers
        $drivers = [
            ['name' => 'Mohammed Hassan', 'phone' => '+212-6-12-34-56-78', 'license_number' => 'DL123456', 'email' => 'mohammed@example.com'],
            ['name' => 'Fatima Zahra', 'phone' => '+212-6-23-45-67-89', 'license_number' => 'DL234567', 'email' => 'fatima@example.com'],
            ['name' => 'Ahmed Benali', 'phone' => '+212-6-34-56-78-90', 'license_number' => 'DL345678', 'email' => 'ahmed@example.com'],
            ['name' => 'Yasmine Alami', 'phone' => '+212-6-45-67-89-01', 'license_number' => 'DL456789', 'email' => 'yasmine@example.com'],
            ['name' => 'Karim Idrissi', 'phone' => '+212-6-56-78-90-12', 'license_number' => 'DL567890', 'email' => 'karim@example.com'],
        ];

        $this->command->info('Creating drivers...');
        $createdDrivers = [];
        foreach ($drivers as $driverData) {
            $createdDrivers[] = Driver::create($driverData);
        }

        // Create Devices with realistic Moroccan locations
        $locations = [
            ['name' => 'Casablanca Downtown', 'lat' => 33.5731, 'lon' => -7.5898],
            ['name' => 'Rabat Center', 'lat' => 34.0209, 'lon' => -6.8416],
            ['name' => 'Marrakech Medina', 'lat' => 31.6295, 'lon' => -7.9811],
            ['name' => 'Tangier Port', 'lat' => 35.7595, 'lon' => -5.8340],
            ['name' => 'Fes Old City', 'lat' => 34.0181, 'lon' => -5.0078],
            ['name' => 'Agadir Beach', 'lat' => 30.4278, 'lon' => -9.5981],
            ['name' => 'Meknes Center', 'lat' => 33.8935, 'lon' => -5.5473],
            ['name' => 'Essaouira Coast', 'lat' => 31.5085, 'lon' => -9.7595],
        ];

        $this->command->info('Creating devices...');
        $createdDevices = [];
        foreach ($locations as $index => $location) {
            $isOnline = $index < 5; // First 5 devices are online
            $device = Device::create([
                'flespi_device_id' => 7584428 + $index,
                'name' => 'Vehicle ' . chr(65 + $index), // Vehicle A, B, C, etc.
                'ident' => '929228' . str_pad($index, 2, '0', STR_PAD_LEFT),
                'device_type_id' => 670,
                'current_driver_id' => $createdDrivers[$index % count($createdDrivers)]->id,
                'status' => $isOnline ? 'online' : 'offline',
                'last_latitude' => $location['lat'] + (rand(-100, 100) / 1000),
                'last_longitude' => $location['lon'] + (rand(-100, 100) / 1000),
                'last_speed' => $isOnline ? rand(0, 80) : 0,
                'last_message_at' => $isOnline ? now()->subMinutes(rand(1, 10)) : now()->subHours(rand(5, 24)),
            ]);
            $createdDevices[] = $device;

            // Create driver assignment
            DriverAssignment::create([
                'device_id' => $device->id,
                'driver_id' => $device->current_driver_id,
                'start_time' => now()->subDays(rand(1, 30)),
                'end_time' => null,
            ]);
        }

        // Create Trips for the last 7 days
        $this->command->info('Creating trips...');
        $tripCount = 0;
        foreach ($createdDevices as $device) {
            $numTrips = rand(3, 8); // 3-8 trips per device

            for ($i = 0; $i < $numTrips; $i++) {
                $startTime = now()->subDays(rand(0, 7))->subHours(rand(0, 23));
                $duration = rand(1800, 14400); // 30 min to 4 hours
                $endTime = $startTime->copy()->addSeconds($duration);
                $distance = rand(5, 150); // 5-150 km

                // Random start location near device location
                $startLat = $device->last_latitude + (rand(-50, 50) / 100);
                $startLon = $device->last_longitude + (rand(-50, 50) / 100);

                // Random end location
                $endLat = $startLat + (rand(-20, 20) / 100);
                $endLon = $startLon + (rand(-20, 20) / 100);

                Trip::create([
                    'flespi_interval_id' => 1000 + $tripCount,
                    'device_id' => $device->id,
                    'driver_id' => $device->current_driver_id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'duration' => $duration,
                    'distance' => $distance,
                    'avg_speed' => round($distance / ($duration / 3600), 2),
                    'max_speed' => rand(60, 120),
                    'start_latitude' => $startLat,
                    'start_longitude' => $startLon,
                    'end_latitude' => $endLat,
                    'end_longitude' => $endLon,
                    'route' => $this->generateRoutePoints($startLat, $startLon, $endLat, $endLon),
                ]);

                $tripCount++;
            }
        }

        // Create Geofences
        $this->command->info('Creating geofences...');
        $geofences = [
            [
                'name' => 'Casablanca Warehouse',
                'type' => 'circle',
                'geometry' => [
                    'type' => 'circle',
                    'center' => ['lat' => 33.5731, 'lon' => -7.5898],
                    'radius' => 1000,
                ],
                'color' => '#3B82F6',
            ],
            [
                'name' => 'Rabat Office',
                'type' => 'circle',
                'geometry' => [
                    'type' => 'circle',
                    'center' => ['lat' => 34.0209, 'lon' => -6.8416],
                    'radius' => 500,
                ],
                'color' => '#10B981',
            ],
            [
                'name' => 'Marrakech Distribution Center',
                'type' => 'polygon',
                'geometry' => [
                    'type' => 'polygon',
                    'coordinates' => [[
                        [-7.9811, 31.6295],
                        [-7.9711, 31.6295],
                        [-7.9711, 31.6395],
                        [-7.9811, 31.6395],
                        [-7.9811, 31.6295],
                    ]],
                ],
                'color' => '#F59E0B',
            ],
        ];

        foreach ($geofences as $geofenceData) {
            Geofence::create($geofenceData);
        }

        $this->command->info('✓ Demo data created successfully!');
        $this->command->info("  - {$createdDrivers->count()} drivers");
        $this->command->info("  - {$createdDevices->count()} devices");
        $this->command->info("  - {$tripCount} trips");
        $this->command->info("  - 3 geofences");
    }

    /**
     * Generate route points between start and end locations
     */
    private function generateRoutePoints(float $startLat, float $startLon, float $endLat, float $endLon): array
    {
        $points = [];
        $numPoints = rand(10, 20);

        for ($i = 0; $i <= $numPoints; $i++) {
            $ratio = $i / $numPoints;
            $lat = $startLat + ($endLat - $startLat) * $ratio + (rand(-5, 5) / 1000);
            $lon = $startLon + ($endLon - $startLon) * $ratio + (rand(-5, 5) / 1000);

            $points[] = [
                'latitude' => $lat,
                'longitude' => $lon,
                'speed' => rand(40, 90),
                'timestamp' => time() - (($numPoints - $i) * 60),
            ];
        }

        return $points;
    }
}
