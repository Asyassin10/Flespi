<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Driver Assignment model for tracking driver-device assignments
 *
 * @property int $id
 * @property int $device_id
 * @property int $driver_id
 * @property string $start_time
 * @property string|null $end_time
 * @property string|null $notes
 */
class DriverAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'driver_id',
        'start_time',
        'end_time',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the device for this assignment
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get the driver for this assignment
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Check if assignment is currently active
     */
    public function isActive(): bool
    {
        return is_null($this->end_time);
    }

    /**
     * Scope for active assignments
     */
    public function scopeActive($query)
    {
        return $query->whereNull('end_time');
    }

    /**
     * Scope for ended assignments
     */
    public function scopeEnded($query)
    {
        return $query->whereNotNull('end_time');
    }
}
