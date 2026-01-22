@extends('layouts.app')

@section('title', 'Add New Driver - Flespi Fleet Management')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('drivers.index') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
            ← Back to Drivers
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Add New Driver</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('drivers.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">
                        Phone Number
                    </label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email Address
                    </label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- License Number -->
                <div>
                    <label for="license_number" class="block text-sm font-medium text-gray-700">
                        License Number
                    </label>
                    <input type="text" name="license_number" id="license_number" value="{{ old('license_number') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('license_number') border-red-500 @enderror">
                    @error('license_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- RFID Card -->
                <div>
                    <label for="rfid_card" class="block text-sm font-medium text-gray-700">
                        RFID Card Number
                    </label>
                    <input type="text" name="rfid_card" id="rfid_card" value="{{ old('rfid_card') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('rfid_card') border-red-500 @enderror">
                    @error('rfid_card')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Used for automatic driver identification</p>
                </div>

                <!-- Active Status -->
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        {{ old('is_active', true) ? 'checked' : '' }}
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                        Active (driver can be assigned to devices)
                    </label>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t">
                    <a href="{{ route('drivers.index') }}"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">
                        Create Driver
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
