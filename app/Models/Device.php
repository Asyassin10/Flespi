<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Device model for GPS tracking devices
 *
 * @property int $id
 * @property int $flespi_device_id
 * @property string $name
 * @property string|null $ident
 * @property int|null $device_type_id
 * @property int|null $current_driver_id
 * @property string $status
 * @property float|null $last_latitude
 * @property float|null $last_longitude
 * @property float|null $last_speed
 * @property string|null $last_message_at
 * @property array|null $telemetry
 */
class Device extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'flespi_device_id',
        'name',
        'ident',
        'device_type_id',
        'current_driver_id',
        'status',
        'last_latitude',
        'last_longitude',
        'last_speed',
        'last_message_at',
        'telemetry',
    ];

    protected $casts = [
        'flespi_device_id' => 'integer',
        'device_type_id' => 'integer',
        'last_latitude' => 'decimal:8',
        'last_longitude' => 'decimal:8',
        'last_speed' => 'decimal:2',
        'last_message_at' => 'datetime',
        'telemetry' => 'array',
    ];

    /**
     * Get the current driver assigned to this device
     */
    public function currentDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'current_driver_id');
    }

    /**
     * Get all driver assignments for this device
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(DriverAssignment::class);
    }

    /**
     * Get current active assignment
     */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(DriverAssignment::class)
            ->whereNull('end_time')
            ->latest('start_time');
    }

    /**
     * Get all trips for this device
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Check if device is online
     */
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    /**
     * Check if device has location data
     */
    public function hasLocation(): bool
    {
        return !is_null($this->last_latitude) && !is_null($this->last_longitude);
    }
}
