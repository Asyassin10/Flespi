<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Driver model for managing driver information
 *
 * @property int $id
 * @property string $name
 * @property string|null $phone
 * @property string|null $license_number
 * @property string|null $rfid_card
 * @property string|null $email
 * @property string|null $notes
 * @property bool $is_active
 */
class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'license_number',
        'rfid_card',
        'email',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all assignments for this driver
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
     * Get all trips for this driver
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Get devices currently assigned to this driver
     */
    public function currentDevices(): HasMany
    {
        return $this->hasMany(Device::class, 'current_driver_id');
    }
}
