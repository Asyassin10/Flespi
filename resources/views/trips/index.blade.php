@extends('layouts.app')

@section('title', 'Trips - Flespi Fleet Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Trips</h1>
        <form action="{{ route('trips.sync') }}" method="POST">
            @csrf
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                Sync Trips
            </button>
        </form>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('trips.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Device</label>
                <select name="device_id" class="w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">All Devices</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->id }}" {{ request('device_id') == $device->id ? 'selected' : '' }}>
                            {{ $device->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Driver</label>
                <select name="driver_id" class="w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">All Drivers</option>
                    @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                            {{ $driver->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                <input type="date" name="from" value="{{ request('from', now()->subDays(7)->format('Y-m-d')) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                <input type="date" name="to" value="{{ request('to', now()->format('Y-m-d')) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm">
            </div>

            <div class="md:col-span-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                    Apply Filters
                </button>
                <a href="{{ route('trips.index') }}" class="ml-2 bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg inline-block">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm">Total Trips</p>
            <p class="text-3xl font-bold text-gray-900">{{ $trips->total() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm">Total Distance</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($totalDistance, 2) }} km</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm">Average Distance</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($avgDistance, 2) }} km</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm">Average Speed</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($avgSpeed, 2) }} km/h</p>
        </div>
    </div>

    <!-- Trips Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Distance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Speed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($trips as $trip)
                        <tr class="hover:bg-gray-50">
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
                                {{ $trip->start_time->format('M d, H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $trip->end_time->format('M d, H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                {{ number_format($trip->distance, 2) }} km
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $trip->getDurationFormatted() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $trip->avg_speed ? number_format($trip->avg_speed, 1) . ' km/h' : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('trips.show', $trip) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                    View Route
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p class="mt-2">No trips found for the selected filters.</p>
                                <p class="mt-1 text-sm">Try adjusting your date range or <a href="{{ route('trips.index') }}" class="text-blue-600">clearing filters</a>.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($trips->hasPages())
            <div class="px-6 py-4 bg-gray-50">
                {{ $trips->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
