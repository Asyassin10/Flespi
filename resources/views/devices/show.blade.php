@extends('layouts.app')

@section('title', $device->name . ' - Flespi Fleet Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <a href="{{ route('devices.index') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Back to Devices
            </a>
            <div class="flex items-center space-x-3">
                <h1 class="text-3xl font-bold text-gray-900">{{ $device->name }}</h1>
                @if($device->isOnline())
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <span class="w-2 h-2 mr-2 bg-green-600 rounded-full"></span>
                        Online
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                        <span class="w-2 h-2 mr-2 bg-gray-600 rounded-full"></span>
                        Offline
                    </span>
                @endif
            </div>
            <p class="text-gray-600 mt-1">{{ $device->ident }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Device Info -->
        <div class="space-y-6">
            <!-- Basic Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Device Information</h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $device->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Identifier</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $device->ident }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Flespi ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $device->flespi_device_id }}</dd>
                    </div>
                    @if($device->last_message_at)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Message</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $device->last_message_at->diffForHumans() }}</dd>
                    </div>
                    @endif
                    @if($device->last_speed !== null)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Current Speed</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($device->last_speed, 1) }} km/h</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <!-- Driver Assignment -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Driver Assignment</h2>

                @if($device->currentDriver)
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Current Driver:</span>
                            <form action="{{ route('devices.unassign-driver', $device) }}" method="POST" class="inline"
                                onsubmit="return confirm('Unassign this driver?');">
                                @csrf
                                <button type="submit" class="text-xs text-red-600 hover:text-red-800">
                                    Unassign
                                </button>
                            </form>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <a href="{{ route('drivers.show', $device->currentDriver) }}" class="font-medium text-blue-900 hover:text-blue-700">
                                {{ $device->currentDriver->name }}
                            </a>
                            @if($device->currentDriver->phone)
                                <p class="text-sm text-blue-700 mt-1">{{ $device->currentDriver->phone }}</p>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500 mb-4">No driver currently assigned</p>
                @endif

                <form action="{{ route('devices.assign-driver', $device) }}" method="POST">
                    @csrf
                    <div class="space-y-3">
                        <label for="driver_id" class="block text-sm font-medium text-gray-700">
                            Assign New Driver
                        </label>
                        <select name="driver_id" id="driver_id" required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select driver...</option>
                            @foreach(\App\Models\Driver::where('is_active', true)->orderBy('name')->get() as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit"
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Assign Driver
                        </button>
                    </div>
                </form>
            </div>

            <!-- Telemetry -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Live Telemetry</h2>
                <div id="telemetry-data" class="space-y-2">
                    <div class="text-center text-gray-400 py-4">
                        <svg class="animate-spin h-8 w-8 mx-auto text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-2 text-sm">Loading...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map and Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Map -->
            @if($device->hasLocation())
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Current Location</h2>
                <div id="map" class="map-container-full"></div>
            </div>
            @else
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-center text-gray-500 py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <p class="mt-2">No location data available</p>
                </div>
            </div>
            @endif

            <!-- Recent Trips -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Trips</h2>
                </div>
                @if($device->trips && $device->trips->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Distance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($device->trips as $trip)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $trip->start_time->format('M d, H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($trip->driver)
                                        <a href="{{ route('drivers.show', $trip->driver) }}" class="text-blue-600 hover:text-blue-800">
                                            {{ $trip->driver->name }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $trip->getDurationFormatted() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ number_format($trip->distance, 2) }} km
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="{{ route('trips.show', $trip) }}" class="text-blue-600 hover:text-blue-800">
                                        View Route
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="p-6 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                    <p class="mt-2">No trips recorded yet</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    @if($device->hasLocation())
    // Initialize map
    const map = L.map('map').setView([{{ $device->last_latitude }}, {{ $device->last_longitude }}], 15);

    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Add device marker
    const icon = L.divIcon({
        className: 'custom-marker',
        html: '<div class="bg-{{ $device->isOnline() ? 'green' : 'gray' }}-500 text-white rounded-full w-12 h-12 flex items-center justify-center text-sm font-bold shadow-lg">{{ substr($device->name, 0, 2) }}</div>',
        iconSize: [48, 48],
        iconAnchor: [24, 24]
    });

    L.marker([{{ $device->last_latitude }}, {{ $device->last_longitude }}], { icon: icon })
        .addTo(map)
        .bindPopup(`
            <div class="p-2">
                <h3 class="font-bold">{{ $device->name }}</h3>
                <p class="text-sm">{{ $device->ident }}</p>
                @if($device->last_speed !== null)
                <p class="text-sm">Speed: {{ number_format($device->last_speed, 1) }} km/h</p>
                @endif
                @if($device->last_message_at)
                <p class="text-xs text-gray-600">{{ $device->last_message_at->diffForHumans() }}</p>
                @endif
            </div>
        `).openPopup();
    @endif

    // Load telemetry data
    async function loadTelemetry() {
        try {
            const response = await fetch('/api/devices/{{ $device->id }}/telemetry');
            const data = await response.json();

            const telemetryDiv = document.getElementById('telemetry-data');
            const telemetry = data.telemetry || data;

            // Define interesting parameters
            const params = {
                'battery.voltage': { label: 'Battery', unit: 'V' },
                'external.powersource.voltage': { label: 'Power', unit: 'V' },
                'engine.ignition.status': { label: 'Ignition', unit: '' },
                'position.satellites': { label: 'Satellites', unit: '' },
                'position.altitude': { label: 'Altitude', unit: 'm' },
                'position.direction': { label: 'Direction', unit: '°' },
                'din.1': { label: 'Digital Input 1', unit: '' },
                'din.2': { label: 'Digital Input 2', unit: '' },
                'engine.rpm': { label: 'Engine RPM', unit: '' },
                'temperature.interior': { label: 'Interior Temp', unit: '°C' }
            };

            let html = '<dl class="space-y-2">';
            let found = false;

            for (const [key, config] of Object.entries(params)) {
                if (telemetry[key] !== undefined && telemetry[key] !== null) {
                    let value = telemetry[key];
                    if (typeof value === 'object' && value.value !== undefined) {
                        value = value.value;
                    }

                    html += `
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <dt class="text-sm text-gray-600">${config.label}</dt>
                            <dd class="text-sm font-medium text-gray-900">${value} ${config.unit}</dd>
                        </div>
                    `;
                    found = true;
                }
            }

            // If no specific params found, show all
            if (!found) {
                for (const [key, value] of Object.entries(telemetry)) {
                    if (key === 'timestamp' || key === 'server.timestamp') continue;
                    let displayValue = value;
                    if (typeof value === 'object' && value.value !== undefined) {
                        displayValue = value.value;
                    }
                    html += `
                        <div class="flex justify-between py-2 border-b border-gray-100">
                            <dt class="text-xs text-gray-600 truncate max-w-[150px]">${key}</dt>
                            <dd class="text-xs font-medium text-gray-900">${displayValue}</dd>
                        </div>
                    `;
                }
            }

            html += '</dl>';

            if (!found && Object.keys(telemetry).length === 0) {
                html = '<p class="text-sm text-gray-500 text-center py-4">No telemetry data available</p>';
            }

            telemetryDiv.innerHTML = html;
        } catch (error) {
            document.getElementById('telemetry-data').innerHTML =
                '<p class="text-sm text-red-600 text-center py-4">Failed to load telemetry</p>';
        }
    }

    // Load telemetry on page load
    loadTelemetry();

    // Refresh telemetry every 30 seconds
    setInterval(loadTelemetry, 30000);
</script>
@endpush
