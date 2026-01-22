@extends('layouts.app')

@section('title', 'Geofences - Flespi Fleet Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Geofences</h1>
        <div class="flex space-x-3">
            <form action="{{ route('geofences.sync') }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Sync from Flespi
                </button>
            </form>
            <a href="{{ route('geofences.create') }}"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Create Geofence
            </a>
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

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Geofences</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $geofences->count() }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Circle Geofences</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $geofences->where('type', 'circle')->count() }}</p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Polygon Geofences</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $geofences->where('type', 'polygon')->count() }}</p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Map showing all geofences -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Geofences Map</h2>
        <div id="map" class="map-container-full"></div>
    </div>

    <!-- Geofences List -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">All Geofences</h2>
        </div>

        @if($geofences->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Color</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($geofences as $geofence)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $geofence->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $geofence->type === 'circle' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ ucfirst($geofence->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-6 h-6 rounded-full border-2" style="background-color: {{ $geofence->color }}"></div>
                                    <span class="ml-2 text-sm text-gray-500">{{ $geofence->color }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $geofence->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="{{ route('geofences.show', $geofence) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                <a href="{{ route('geofences.edit', $geofence) }}" class="text-green-600 hover:text-green-900">Edit</a>
                                <form action="{{ route('geofences.destroy', $geofence) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Are you sure you want to delete this geofence?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <p class="mt-2">No geofences created yet</p>
                <a href="{{ route('geofences.create') }}" class="mt-4 inline-block text-blue-600 hover:text-blue-800">
                    Create your first geofence
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initialize map centered on Morocco
    const map = L.map('map').setView([33.5731, -7.5898], 6);

    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Geofences data
    const geofences = @json($geofences);

    // Draw each geofence on the map
    geofences.forEach(geofence => {
        const geometry = geofence.geometry;
        const color = geofence.color || '#3B82F6';

        if (geometry.type === 'circle') {
            const circle = L.circle(
                [geometry.center.lat, geometry.center.lon],
                {
                    color: color,
                    fillColor: color,
                    fillOpacity: 0.2,
                    radius: geometry.radius
                }
            ).addTo(map);

            circle.bindPopup(`
                <div class="p-2">
                    <h3 class="font-bold">${geofence.name}</h3>
                    <p class="text-sm text-gray-600">Type: Circle</p>
                    <p class="text-sm text-gray-600">Radius: ${Math.round(geometry.radius)}m</p>
                    <a href="/geofences/${geofence.id}" class="text-blue-600 hover:text-blue-800 text-sm">View Details</a>
                </div>
            `);
        } else if (geometry.type === 'polygon') {
            // Convert [lon, lat] to [lat, lon]
            const latlngs = geometry.coordinates[0].map(coord => [coord[1], coord[0]]);

            const polygon = L.polygon(latlngs, {
                color: color,
                fillColor: color,
                fillOpacity: 0.2
            }).addTo(map);

            polygon.bindPopup(`
                <div class="p-2">
                    <h3 class="font-bold">${geofence.name}</h3>
                    <p class="text-sm text-gray-600">Type: Polygon</p>
                    <p class="text-sm text-gray-600">Points: ${latlngs.length}</p>
                    <a href="/geofences/${geofence.id}" class="text-blue-600 hover:text-blue-800 text-sm">View Details</a>
                </div>
            `);
        }
    });

    // Fit map bounds if we have geofences
    if (geofences.length > 0) {
        const group = new L.featureGroup();

        geofences.forEach(geofence => {
            const geometry = geofence.geometry;
            if (geometry.type === 'circle') {
                L.circle([geometry.center.lat, geometry.center.lon], {radius: geometry.radius}).addTo(group);
            } else if (geometry.type === 'polygon') {
                const latlngs = geometry.coordinates[0].map(coord => [coord[1], coord[0]]);
                L.polygon(latlngs).addTo(group);
            }
        });

        map.fitBounds(group.getBounds().pad(0.1));
    }
</script>
@endpush
