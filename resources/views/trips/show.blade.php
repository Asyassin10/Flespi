@extends('layouts.app')

@section('title', 'Trip Details - Flespi Fleet Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <a href="{{ route('trips.index') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Back to Trips
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Trip Details</h1>
        </div>
    </div>

    <!-- Trip Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Device & Driver Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Trip Information</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Device</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <a href="{{ route('devices.show', $trip->device) }}" class="text-blue-600 hover:text-blue-800">
                            {{ $trip->device->name }}
                        </a>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Driver</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($trip->driver)
                            <a href="{{ route('drivers.show', $trip->driver) }}" class="text-blue-600 hover:text-blue-800">
                                {{ $trip->driver->name }}
                            </a>
                        @else
                            <span class="text-gray-400">Not assigned</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Flespi Interval ID</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $trip->flespi_interval_id ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Time Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Time & Duration</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Start Time</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $trip->start_time->format('F d, Y - H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">End Time</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $trip->end_time->format('F d, Y - H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Duration</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $trip->getDurationFormatted() }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Distance</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($trip->distance, 2) }}</p>
                    <p class="text-sm text-gray-500">kilometers</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Average Speed</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($trip->avg_speed ?? 0, 1) }}</p>
                    <p class="text-sm text-gray-500">km/h</p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Max Speed</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($trip->max_speed ?? 0, 1) }}</p>
                    <p class="text-sm text-gray-500">km/h</p>
                </div>
                <div class="bg-red-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Duration</p>
                    <p class="text-2xl font-bold text-gray-900">{{ floor($trip->duration / 3600) }}</p>
                    <p class="text-sm text-gray-500">hours {{ floor(($trip->duration % 3600) / 60) }} min</p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Map with Route -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Trip Route</h2>
        <div id="map" class="map-container-full"></div>
    </div>

    <!-- Locations -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Start Location</h3>
            <dl class="space-y-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Latitude</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $trip->start_latitude ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Longitude</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $trip->start_longitude ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">End Location</h3>
            <dl class="space-y-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Latitude</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $trip->end_latitude ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Longitude</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $trip->end_longitude ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initialize map
    const map = L.map('map');

    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Trip data
    const trip = @json($trip);
    const route = @json($routePoints);

    // Start and end markers
    if (trip.start_latitude && trip.start_longitude) {
        const startIcon = L.divIcon({
            className: 'custom-marker',
            html: '<div class="bg-green-500 text-white rounded-full w-10 h-10 flex items-center justify-center text-sm font-bold">S</div>',
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        L.marker([trip.start_latitude, trip.start_longitude], { icon: startIcon })
            .addTo(map)
            .bindPopup(`
                <div class="p-2">
                    <h3 class="font-bold text-green-600">Start Point</h3>
                    <p class="text-sm">${new Date(trip.start_time).toLocaleString()}</p>
                </div>
            `);
    }

    if (trip.end_latitude && trip.end_longitude) {
        const endIcon = L.divIcon({
            className: 'custom-marker',
            html: '<div class="bg-red-500 text-white rounded-full w-10 h-10 flex items-center justify-center text-sm font-bold">E</div>',
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        L.marker([trip.end_latitude, trip.end_longitude], { icon: endIcon })
            .addTo(map)
            .bindPopup(`
                <div class="p-2">
                    <h3 class="font-bold text-red-600">End Point</h3>
                    <p class="text-sm">${new Date(trip.end_time).toLocaleString()}</p>
                </div>
            `);
    }

    // Draw route if available
    if (route && route.length > 0) {
        const latlngs = route.map(point => [point.latitude, point.longitude]);

        // Draw polyline
        const polyline = L.polyline(latlngs, {
            color: '#3B82F6',
            weight: 4,
            opacity: 0.7
        }).addTo(map);

        // Fit map to route
        map.fitBounds(polyline.getBounds(), { padding: [50, 50] });

    } else if (trip.start_latitude && trip.end_latitude) {
        // If no route, just show start and end
        const bounds = L.latLngBounds(
            [trip.start_latitude, trip.start_longitude],
            [trip.end_latitude, trip.end_longitude]
        );
        map.fitBounds(bounds, { padding: [50, 50] });
    } else {
        // Default view
        map.setView([33.5731, -7.5898], 13);
    }
</script>
@endpush
