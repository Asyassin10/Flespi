@extends('layouts.app')

@section('title', $geofence->name . ' - Flespi Fleet Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <a href="{{ route('geofences.index') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Back to Geofences
            </a>
            <h1 class="text-3xl font-bold text-gray-900">{{ $geofence->name }}</h1>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('geofences.edit', $geofence) }}"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Edit Geofence
            </a>
            <form action="{{ route('geofences.destroy', $geofence) }}" method="POST" class="inline"
                onsubmit="return confirm('Are you sure you want to delete this geofence?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Delete
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    <!-- Geofence Information -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Details</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $geofence->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Type</dt>
                    <dd class="mt-1">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $geofence->type === 'circle' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                            {{ ucfirst($geofence->type) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Color</dt>
                    <dd class="mt-1 flex items-center">
                        <div class="w-8 h-8 rounded-full border-2" style="background-color: {{ $geofence->color }}"></div>
                        <span class="ml-2 text-sm text-gray-900">{{ $geofence->color }}</span>
                    </dd>
                </div>
                @if($geofence->flespi_geofence_id)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Flespi ID</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $geofence->flespi_geofence_id }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-sm font-medium text-gray-500">Created</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $geofence->created_at->format('F d, Y H:i') }}</dd>
                </div>
                @if($geofence->updated_at != $geofence->created_at)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $geofence->updated_at->format('F d, Y H:i') }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Geometry</h2>
            <dl class="space-y-3">
                @if($geofence->type === 'circle')
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Center Latitude</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono">{{ number_format($geofence->geometry['center']['lat'], 6) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Center Longitude</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono">{{ number_format($geofence->geometry['center']['lon'], 6) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Radius</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($geofence->geometry['radius'], 0) }} meters</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Area</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            ~{{ number_format(3.14159 * pow($geofence->geometry['radius'], 2) / 1000000, 2) }} km²
                        </dd>
                    </div>
                @else
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Points</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ count($geofence->geometry['coordinates'][0]) }} vertices</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Perimeter</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @php
                                $coords = $geofence->geometry['coordinates'][0];
                                $perimeter = 0;
                                for ($i = 0; $i < count($coords) - 1; $i++) {
                                    $lat1 = $coords[$i][1];
                                    $lon1 = $coords[$i][0];
                                    $lat2 = $coords[$i+1][1];
                                    $lon2 = $coords[$i+1][0];
                                    $distance = sqrt(pow($lat2 - $lat1, 2) + pow($lon2 - $lon1, 2)) * 111000;
                                    $perimeter += $distance;
                                }
                            @endphp
                            ~{{ number_format($perimeter, 0) }} meters
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Assignment</h2>
            <form action="{{ route('geofences.assign-calculator', $geofence) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="calculator_id" class="block text-sm font-medium text-gray-700">
                        Assign to Calculator
                    </label>
                    <input type="number" name="calculator_id" id="calculator_id"
                        value="{{ config('services.flespi.trip_calculator_id') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Calculator ID">
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Assign to Calculator
                </button>
            </form>

            <div class="mt-4 pt-4 border-t">
                <p class="text-xs text-gray-500">
                    Assigning to a calculator enables entry/exit detection for trips
                </p>
            </div>
        </div>
    </div>

    <!-- Map -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Map View</h2>
        <div id="map" class="map-container-full"></div>
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

    // Geofence data
    const geofence = @json($geofence);
    const geometry = geofence.geometry;
    const color = geofence.color || '#3B82F6';

    let shape;

    if (geometry.type === 'circle') {
        // Draw circle
        shape = L.circle(
            [geometry.center.lat, geometry.center.lon],
            {
                color: color,
                fillColor: color,
                fillOpacity: 0.2,
                radius: geometry.radius
            }
        ).addTo(map);

        // Center map on circle
        map.setView([geometry.center.lat, geometry.center.lon], 13);

        // Add center marker
        L.marker([geometry.center.lat, geometry.center.lon], {
            icon: L.divIcon({
                className: 'custom-marker',
                html: '<div class="bg-blue-500 text-white rounded-full w-8 h-8 flex items-center justify-center text-xs font-bold">C</div>',
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            })
        }).addTo(map).bindPopup('Center of ' + geofence.name);

    } else if (geometry.type === 'polygon') {
        // Convert [lon, lat] to [lat, lon]
        const latlngs = geometry.coordinates[0].map(coord => [coord[1], coord[0]]);

        // Draw polygon
        shape = L.polygon(latlngs, {
            color: color,
            fillColor: color,
            fillOpacity: 0.2
        }).addTo(map);

        // Fit map to polygon bounds
        map.fitBounds(shape.getBounds(), { padding: [50, 50] });
    }

    // Add popup to shape
    if (shape) {
        shape.bindPopup(`
            <div class="p-2">
                <h3 class="font-bold text-lg">${geofence.name}</h3>
                <p class="text-sm text-gray-600">Type: ${geometry.type}</p>
                ${geometry.type === 'circle' ? `<p class="text-sm text-gray-600">Radius: ${Math.round(geometry.radius)}m</p>` : ''}
            </div>
        `);
    }
</script>
@endpush
