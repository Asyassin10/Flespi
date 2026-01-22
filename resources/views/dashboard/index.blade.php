@extends('layouts.app')

@section('title', 'Dashboard - Flespi Fleet Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <div class="flex space-x-3">
            <form action="{{ route('trips.sync') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    Sync Trips
                </button>
            </form>
            <form action="{{ route('setup.sync') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                    Sync Devices
                </button>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Devices -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Devices</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $totalDevices }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Online Devices -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Online Devices</p>
                    <p class="text-3xl font-bold text-green-600">{{ $onlineDevices }}</p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Trips -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Trips Today</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $tripsToday }}</p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Drivers -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Drivers</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $totalDrivers }}</p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Map -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Live Device Locations</h2>
        <div id="map" class="map-container-full"></div>
    </div>

    <!-- Recent Trips -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-900">Recent Trips</h2>
            <a href="{{ route('trips.index') }}" class="text-blue-600 hover:text-blue-800">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Distance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentTrips as $trip)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('devices.show', $trip->device) }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $trip->device->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($trip->driver)
                                    <a href="{{ route('drivers.show', $trip->driver) }}" class="text-blue-600 hover:text-blue-800">
                                        {{ $trip->driver->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $trip->start_time->format('M d, Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ number_format($trip->distance, 2) }} km
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $trip->getDurationFormatted() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('trips.show', $trip) }}" class="text-blue-600 hover:text-blue-800">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No trips found. <a href="{{ route('setup') }}" class="text-blue-600">Run setup</a> to sync data.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initialize map
    const map = L.map('map').setView([33.5731, -7.5898], 6); // Default to Morocco

    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Device data from backend
    const devices = @json($devices);

    // Add markers for each device
    const markers = [];
    let bounds = [];

    devices.forEach(device => {
        if (device.last_latitude && device.last_longitude) {
            const isOnline = device.status === 'online';

            // Create custom icon
            const icon = L.divIcon({
                className: 'custom-marker',
                html: `<div class="relative">
                    <svg class="h-8 w-8 ${isOnline ? 'text-green-500' : 'text-gray-400'}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    </svg>
                    ${isOnline ? '<div class="absolute top-0 right-0 h-2 w-2 bg-green-500 rounded-full animate-pulse"></div>' : ''}
                </div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 32]
            });

            const marker = L.marker([device.last_latitude, device.last_longitude], { icon })
                .addTo(map)
                .bindPopup(`
                    <div class="p-2">
                        <h3 class="font-bold">${device.name}</h3>
                        <p class="text-sm text-gray-600">Status: <span class="${isOnline ? 'text-green-600' : 'text-gray-400'}">${device.status}</span></p>
                        ${device.last_speed ? `<p class="text-sm">Speed: ${device.last_speed} km/h</p>` : ''}
                        ${device.current_driver ? `<p class="text-sm">Driver: ${device.current_driver.name}</p>` : ''}
                        <a href="/devices/${device.id}" class="text-blue-600 text-sm hover:underline">View Details</a>
                    </div>
                `);

            markers.push(marker);
            bounds.push([device.last_latitude, device.last_longitude]);
        }
    });

    // Fit map to show all devices
    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [50, 50] });
    }

    // Auto-refresh every 30 seconds
    setInterval(() => {
        window.location.reload();
    }, 30000);
</script>
@endpush
