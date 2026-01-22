@extends('layouts.app')

@section('title', 'Create Geofence - Flespi Fleet Management')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
<style>
    .leaflet-draw-tooltip { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <div class="mb-6">
        <a href="{{ route('geofences.index') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
            ← Back to Geofences
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Create Geofence</h1>
        <p class="text-gray-600 mt-2">Draw a circle or polygon on the map to define your geofence</p>
    </div>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6">
                <form id="geofenceForm" action="{{ route('geofences.store') }}" method="POST">
                    @csrf

                    <div class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Geofence Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Geofence Type <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="type" value="circle" checked
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="ml-2 text-sm text-gray-700">Circle (fixed radius)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="type" value="polygon"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="ml-2 text-sm text-gray-700">Polygon (custom shape)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Color -->
                        <div>
                            <label for="color" class="block text-sm font-medium text-gray-700">
                                Color
                            </label>
                            <input type="color" name="color" id="color" value="#3B82F6"
                                class="mt-1 block w-full h-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <!-- Hidden geometry field -->
                        <input type="hidden" name="geometry" id="geometry" required>

                        <!-- Instructions -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-blue-900 mb-2">How to draw:</h3>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li><strong>Circle:</strong> Click on map to set center, drag to set radius</li>
                                <li><strong>Polygon:</strong> Click to add points, double-click to finish</li>
                                <li>Use toolbar on map to draw</li>
                            </ul>
                        </div>

                        <!-- Submit -->
                        <div class="flex items-center justify-end space-x-3 pt-6 border-t">
                            <a href="{{ route('geofences.index') }}"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" id="submitBtn" disabled
                                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                                Create Geofence
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Map -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Draw Geofence on Map</h2>
                <div id="map" style="height: 600px; border-radius: 0.5rem;"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script>
    // Initialize map centered on Morocco
    const map = L.map('map').setView([33.5731, -7.5898], 10);

    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Feature group to store drawn shapes
    const drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    let currentShape = null;
    const submitBtn = document.getElementById('submitBtn');
    const geometryInput = document.getElementById('geometry');
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const colorInput = document.getElementById('color');

    // Draw control
    const drawControl = new L.Control.Draw({
        position: 'topright',
        draw: {
            polyline: false,
            rectangle: false,
            marker: false,
            circlemarker: false,
            circle: {
                shapeOptions: {
                    color: '#3B82F6',
                    fillColor: '#3B82F6',
                    fillOpacity: 0.2
                }
            },
            polygon: {
                allowIntersection: false,
                shapeOptions: {
                    color: '#3B82F6',
                    fillColor: '#3B82F6',
                    fillOpacity: 0.2
                }
            }
        },
        edit: {
            featureGroup: drawnItems,
            remove: true
        }
    });
    map.addControl(drawControl);

    // Update draw control when type changes
    typeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Clear existing shape
            drawnItems.clearLayers();
            currentShape = null;
            submitBtn.disabled = true;
            geometryInput.value = '';
        });
    });

    // Update color when changed
    colorInput.addEventListener('change', function() {
        if (currentShape) {
            currentShape.setStyle({
                color: this.value,
                fillColor: this.value
            });
        }
    });

    // Handle shape creation
    map.on(L.Draw.Event.CREATED, function(event) {
        const layer = event.layer;
        const type = event.layerType;

        // Remove previous shape
        drawnItems.clearLayers();

        // Add new shape
        drawnItems.addLayer(layer);
        currentShape = layer;

        // Apply current color
        layer.setStyle({
            color: colorInput.value,
            fillColor: colorInput.value,
            fillOpacity: 0.2
        });

        // Generate geometry JSON
        let geometry;
        if (type === 'circle') {
            const center = layer.getLatLng();
            const radius = layer.getRadius();
            geometry = {
                type: 'circle',
                center: {
                    lat: center.lat,
                    lon: center.lng
                },
                radius: radius
            };

            // Set type radio to circle
            document.querySelector('input[value="circle"]').checked = true;
        } else if (type === 'polygon') {
            const latlngs = layer.getLatLngs()[0];
            // Convert to Flespi format: [[lon, lat], [lon, lat], ...]
            const coordinates = latlngs.map(ll => [ll.lng, ll.lat]);
            // Close the polygon
            coordinates.push([latlngs[0].lng, latlngs[0].lat]);

            geometry = {
                type: 'polygon',
                coordinates: [coordinates]
            };

            // Set type radio to polygon
            document.querySelector('input[value="polygon"]').checked = true;
        }

        // Set hidden field
        geometryInput.value = JSON.stringify(geometry);

        // Enable submit button
        submitBtn.disabled = false;
    });

    // Handle shape editing
    map.on(L.Draw.Event.EDITED, function(event) {
        const layers = event.layers;
        layers.eachLayer(function(layer) {
            currentShape = layer;

            // Update geometry
            if (layer instanceof L.Circle) {
                const center = layer.getLatLng();
                const radius = layer.getRadius();
                geometryInput.value = JSON.stringify({
                    type: 'circle',
                    center: {
                        lat: center.lat,
                        lon: center.lng
                    },
                    radius: radius
                });
            } else if (layer instanceof L.Polygon) {
                const latlngs = layer.getLatLngs()[0];
                const coordinates = latlngs.map(ll => [ll.lng, ll.lat]);
                coordinates.push([latlngs[0].lng, latlngs[0].lat]);
                geometryInput.value = JSON.stringify({
                    type: 'polygon',
                    coordinates: [coordinates]
                });
            }
        });
    });

    // Handle shape deletion
    map.on(L.Draw.Event.DELETED, function(event) {
        currentShape = null;
        geometryInput.value = '';
        submitBtn.disabled = true;
    });

    // Form validation
    document.getElementById('geofenceForm').addEventListener('submit', function(e) {
        if (!geometryInput.value) {
            e.preventDefault();
            alert('Please draw a geofence on the map first!');
            return false;
        }
    });
</script>
@endpush
