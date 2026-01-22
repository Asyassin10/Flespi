<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flespi_device_id')->unique();
            $table->string('name');
            $table->string('ident')->nullable(); // IMEI, Serial number, etc.
            $table->unsignedBigInteger('device_type_id')->nullable();
            $table->foreignId('current_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('status')->default('offline'); // online, offline
            $table->decimal('last_latitude', 10, 8)->nullable();
            $table->decimal('last_longitude', 11, 8)->nullable();
            $table->decimal('last_speed', 8, 2)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->json('telemetry')->nullable(); // Store last telemetry data
            $table->timestamps();
            $table->softDeletes();

            $table->index('flespi_device_id');
            $table->index('status');
            $table->index('current_driver_id');
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
