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
        Schema::create('trips_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flespi_interval_id')->nullable()->unique();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->decimal('distance', 10, 2)->default(0); // in km
            $table->integer('duration')->default(0); // in seconds
            $table->decimal('avg_speed', 8, 2)->nullable(); // in km/h
            $table->decimal('max_speed', 8, 2)->nullable(); // in km/h
            $table->decimal('start_latitude', 10, 8)->nullable();
            $table->decimal('start_longitude', 11, 8)->nullable();
            $table->decimal('end_latitude', 10, 8)->nullable();
            $table->decimal('end_longitude', 11, 8)->nullable();
            $table->text('route')->nullable(); // Encoded polyline from Flespi
            $table->json('metadata')->nullable(); // Store additional trip data from Flespi
            $table->timestamps();

            $table->index('flespi_interval_id');
            $table->index(['device_id', 'start_time']);
            $table->index(['driver_id', 'start_time']);
            $table->index('start_time');
            $table->index('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips_cache');
    }
};
