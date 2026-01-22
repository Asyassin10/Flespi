@extends('layouts.app')

@section('title', 'Setup - Flespi Fleet Management')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Flespi Setup Wizard</h1>

        <div class="space-y-6">
            <!-- Step 1: Token Check -->
            <div class="border-l-4 @if($hasToken) border-green-500 @else border-yellow-500 @endif pl-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">1. Flespi API Token</h3>
                        <p class="text-sm text-gray-600 mt-1">
                            @if($hasToken)
                                ✓ Token is configured
                            @else
                                Please add your Flespi token to .env file
                            @endif
                        </p>
                    </div>
                    @if($hasToken)
                        <svg class="h-8 w-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    @else
                        <svg class="h-8 w-8 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                </div>
                @if(!$hasToken)
                    <div class="mt-3 bg-gray-50 p-3 rounded">
                        <code class="text-sm">FLESPI_TOKEN=your_token_here</code>
                    </div>
                @endif
            </div>

            <!-- Step 2: Sync Devices -->
            <div class="border-l-4 @if($deviceCount > 0) border-green-500 @else border-blue-500 @endif pl-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">2. Sync Devices</h3>
                        <p class="text-sm text-gray-600 mt-1">
                            @if($deviceCount > 0)
                                ✓ {{ $deviceCount }} devices synced
                            @else
                                Import your devices from Flespi
                            @endif
                        </p>
                    </div>
                    @if($deviceCount > 0)
                        <svg class="h-8 w-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                </div>
                @if($hasToken)
                    <form action="{{ route('setup.sync') }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                            {{ $deviceCount > 0 ? 'Sync Again' : 'Sync Devices' }}
                        </button>
                    </form>
                @endif
            </div>

            <!-- Step 3: Trip Calculator -->
            <div class="border-l-4 @if($hasCalculator) border-green-500 @else border-blue-500 @endif pl-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">3. Trip Calculator</h3>
                        <p class="text-sm text-gray-600 mt-1">
                            @if($hasCalculator)
                                ✓ Calculator ID: {{ config('services.flespi.trip_calculator_id') }}
                            @else
                                Create a trip calculator in Flespi
                            @endif
                        </p>
                    </div>
                    @if($hasCalculator)
                        <svg class="h-8 w-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                </div>
                @if($hasToken && !$hasCalculator)
                    <form action="{{ route('setup.calculator') }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">
                            Create Calculator
                        </button>
                    </form>
                @endif
            </div>

            <!-- Completion Message -->
            @if($hasToken && $deviceCount > 0 && $hasCalculator)
                <div class="bg-green-50 border-l-4 border-green-500 p-4">
                    <div class="flex">
                        <svg class="h-6 w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-green-800">Setup Complete!</h3>
                            <p class="text-sm text-green-700 mt-1">
                                Your Flespi integration is ready. You can now view devices, trips, and track your fleet.
                            </p>
                            <div class="mt-4 space-x-3">
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                    Go to Dashboard
                                </a>
                                <a href="{{ route('trips.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    View Trips
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Help Section -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-semibold text-gray-900 mb-2">Need Help?</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Get your Flespi token from <a href="https://flespi.io/" target="_blank" class="text-blue-600 hover:underline">flespi.io</a></li>
                    <li>• Make sure your devices are sending data to Flespi</li>
                    <li>• Check Laravel logs if you encounter errors</li>
                    <li>• Run <code class="bg-white px-2 py-1 rounded">php artisan flespi:sync-devices</code> from command line</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
