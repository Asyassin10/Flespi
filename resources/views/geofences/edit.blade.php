@extends('layouts.app')

@section('title', 'Edit ' . $geofence->name . ' - Flespi Fleet Management')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('geofences.show', $geofence) }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
            ← Back to Geofence
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Edit Geofence: {{ $geofence->name }}</h1>
    </div>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('geofences.update', $geofence) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Geofence Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $geofence->name) }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Color -->
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700">
                        Color
                    </label>
                    <div class="mt-1 flex items-center space-x-3">
                        <input type="color" name="color" id="color" value="{{ old('color', $geofence->color) }}"
                            class="h-10 w-20 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="text-sm text-gray-500">{{ old('color', $geofence->color) }}</span>
                    </div>
                </div>

                <!-- Geometry Info (Read-only) -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Current Geometry</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Type:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ ucfirst($geofence->type) }}</dd>
                        </div>
                        @if($geofence->type === 'circle')
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600">Center:</dt>
                                <dd class="text-sm font-mono text-gray-900">
                                    {{ number_format($geofence->geometry['center']['lat'], 6) }},
                                    {{ number_format($geofence->geometry['center']['lon'], 6) }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600">Radius:</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ number_format($geofence->geometry['radius'], 0) }} meters</dd>
                            </div>
                        @else
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600">Points:</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ count($geofence->geometry['coordinates'][0]) }} vertices</dd>
                            </div>
                        @endif
                    </dl>
                    <p class="mt-3 text-xs text-gray-500">
                        Note: To change the geometry, you need to delete and recreate the geofence.
                    </p>
                </div>

                <!-- Preview Map -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Preview
                    </label>
                    <div id="map" style="height: 400px; border-radius: 0.5rem; border: 1px solid #e5e7eb;"></div>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t">
                    <a href="{{ route('geofences.show', $geofence) }}"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">
                        Update Geofence
                    </button>
                </div>
            </div>
        </form>
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
    const geometry = @json($geofence->geometry);
    const colorInput = document.getElementById('color');
    let shape;

    function updateShapeColor() {
        const color = colorInput.value;
        if (shape) {
            shape.setStyle({
                color: color,
                fillColor: color
            });
        }
        document.querySelector('#color + span').textContent = color;
    }

    // Draw geofence
    if (geometry.type === 'circle') {
        shape = L.circle(
            [geometry.center.lat, geometry.center.lon],
            {
                color: colorInput.value,
                fillColor: colorInput.value,
                fillOpacity: 0.2,
                radius: geometry.radius
            }
        ).addTo(map);

        map.setView([geometry.center.lat, geometry.center.lon], 13);
    } else if (geometry.type === 'polygon') {
        const latlngs = geometry.coordinates[0].map(coord => [coord[1], coord[0]]);

        shape = L.polygon(latlngs, {
            color: colorInput.value,
            fillColor: colorInput.value,
            fillOpacity: 0.2
        }).addTo(map);

        map.fitBounds(shape.getBounds(), { padding: [50, 50] });
    }

    // Update color on change
    colorInput.addEventListener('input', updateShapeColor);
</script>
@endpush
