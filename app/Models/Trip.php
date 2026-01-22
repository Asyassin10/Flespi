<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trip model for storing cached trip data from Flespi
 *
 * @property int $id
 * @property int|null $flespi_interval_id
 * @property int $device_id
 * @property int|null $driver_id
 * @property string $start_time
 * @property string $end_time
 * @property float $distance
 * @property int $duration
 * @property float|null $avg_speed
 * @property float|null $max_speed
 * @property float|null $start_latitude
 * @property float|null $start_longitude
 * @property float|null $end_latitude
 * @property float|null $end_longitude
 * @property array|null $route
 * @property array|null $metadata
 */
class Trip extends Model
{
    use HasFactory;

    protected $table = 'trips_cache';

    protected $fillable = [
        'flespi_interval_id',
        'device_id',
        'driver_id',
        'start_time',
        'end_time',
        'distance',
        'duration',
        'avg_speed',
        'max_speed',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'route',
        'metadata',
    ];

    protected $casts = [
        'flespi_interval_id' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'distance' => 'decimal:2',
        'duration' => 'integer',
        'avg_speed' => 'decimal:2',
        'max_speed' => 'decimal:2',
        'start_latitude' => 'decimal:8',
        'start_longitude' => 'decimal:8',
        'end_latitude' => 'decimal:8',
        'end_longitude' => 'decimal:8',
        'route' => 'string', // Encoded polyline from Flespi
        'metadata' => 'array',
    ];

    /**
     * Get the device for this trip
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get the driver for this trip
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get duration in human readable format
     */
    public function getDurationFormatted(): string
    {
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        } else {
            return sprintf('%ds', $seconds);
        }
    }

    /**
     * Scope for trips within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }

    /**
     * Scope for trips by device
     */
    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Scope for trips by driver
     */
    public function scopeByDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }
}
