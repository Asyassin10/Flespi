@extends('layouts.app')

@section('title', 'Devices - Flespi Fleet Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Devices</h1>
        <div class="flex items-center space-x-2 bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-lg">
            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium">Auto-syncing every 2 minutes</span>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm">Total Devices</p>
            <p class="text-3xl font-bold text-gray-900">{{ $devices->total() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm">Online</p>
            <p class="text-3xl font-bold text-green-600">{{ $devices->where('status', 'online')->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-sm">Offline</p>
            <p class="text-3xl font-bold text-gray-400">{{ $devices->where('status', 'offline')->count() }}</p>
        </div>
    </div>

    <!-- Devices Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($devices as $device)
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $device->name }}</h3>
                        <span class="px-3 py-1 rounded-full text-xs font-medium @if($device->status === 'online') bg-green-100 text-green-800 @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst($device->status) }}
                        </span>
                    </div>

                    <dl class="space-y-2">
                        <div>
                            <dt class="text-xs text-gray-500">Device ID</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $device->flespi_device_id }}</dd>
                        </div>

                        @if($device->ident)
                            <div>
                                <dt class="text-xs text-gray-500">Identifier</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $device->ident }}</dd>
                            </div>
                        @endif

                        @if($device->currentDriver)
                            <div>
                                <dt class="text-xs text-gray-500">Current Driver</dt>
                                <dd class="text-sm font-medium text-blue-600">
                                    <a href="{{ route('drivers.show', $device->currentDriver) }}">
                                        {{ $device->currentDriver->name }}
                                    </a>
                                </dd>
                            </div>
                        @endif

                        @if($device->last_speed !== null)
                            <div>
                                <dt class="text-xs text-gray-500">Last Speed</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ number_format($device->last_speed, 1) }} km/h</dd>
                            </div>
                        @endif

                        @if($device->last_message_at)
                            <div>
                                <dt class="text-xs text-gray-500">Last Update</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $device->last_message_at->diffForHumans() }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between">
                        <a href="{{ route('devices.show', $device) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View Details
                        </a>
                        @if($device->hasLocation())
                            <span class="text-green-600 text-sm flex items-center">
                                <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                </svg>
                                Has Location
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">No devices found</h3>
                    <p class="mt-1 text-sm text-gray-500">Auto-sync is active. Devices will appear here automatically once they're synced from Flespi.</p>
                    <div class="mt-6 inline-flex items-center space-x-2 bg-blue-50 border border-blue-200 text-blue-700 px-4 py-2 rounded-lg">
                        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium">Waiting for automatic sync...</span>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($devices->hasPages())
        <div class="bg-white rounded-lg shadow p-4">
            {{ $devices->links() }}
        </div>
    @endif
</div>
@endsection
