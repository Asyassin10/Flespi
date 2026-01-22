@extends('layouts.app')

@section('title', $driver->name . ' - Flespi Fleet Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <a href="{{ route('drivers.index') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Back to Drivers
            </a>
            <h1 class="text-3xl font-bold text-gray-900">{{ $driver->name }}</h1>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('drivers.edit', $driver) }}"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Edit Driver
            </a>
        </div>
    </div>

    <!-- Status Badge -->
    <div>
        @if($driver->is_active)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                <span class="w-2 h-2 mr-2 bg-green-600 rounded-full"></span>
                Active
            </span>
        @else
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                <span class="w-2 h-2 mr-2 bg-gray-600 rounded-full"></span>
                Inactive
            </span>
        @endif
    </div>

    <!-- Driver Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $driver->name }}</dd>
                </div>
                @if($driver->phone)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <a href="tel:{{ $driver->phone }}" class="text-blue-600 hover:text-blue-800">
                            {{ $driver->phone }}
                        </a>
                    </dd>
                </div>
                @endif
                @if($driver->email)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <a href="mailto:{{ $driver->email }}" class="text-blue-600 hover:text-blue-800">
                            {{ $driver->email }}
                        </a>
                    </dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Credentials</h2>
            <dl class="space-y-3">
                @if($driver->license_number)
                <div>
                    <dt class="text-sm font-medium text-gray-500">License Number</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $driver->license_number }}</dd>
                </div>
                @endif
                @if($driver->rfid_card)
                <div>
                    <dt class="text-sm font-medium text-gray-500">RFID Card</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $driver->rfid_card }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $driver->created_at->format('F d, Y') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Current Device Assignment -->
    @if($driver->currentDevices->isNotEmpty())
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Currently Assigned Devices</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($driver->currentDevices as $device)
                <a href="{{ route('devices.show', $device) }}"
                    class="block p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-gray-900">{{ $device->name }}</h3>
                        @if($device->isOnline())
                            <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                        @else
                            <span class="w-3 h-3 bg-gray-400 rounded-full"></span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500">{{ $device->ident }}</p>
                    @if($device->last_message_at)
                    <p class="text-xs text-gray-400 mt-2">
                        Last seen: {{ $device->last_message_at->diffForHumans() }}
                    </p>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Trips -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Recent Trips</h2>
        </div>
        @if($driver->trips()->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Distance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Speed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($driver->trips()->with('device')->orderBy('start_time', 'desc')->limit(10)->get() as $trip)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <a href="{{ route('devices.show', $trip->device) }}" class="text-blue-600 hover:text-blue-800">
                                {{ $trip->device->name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $trip->start_time->format('M d, Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $trip->getDurationFormatted() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ number_format($trip->distance, 2) }} km
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ number_format($trip->avg_speed ?? 0, 1) }} km/h
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
@endsection
