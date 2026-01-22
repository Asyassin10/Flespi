<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Geofence model for managing geofences
 *
 * @property int $id
 * @property int|null $flespi_geofence_id
 * @property string $name
 * @property string $type
 * @property array $geometry
 * @property string $color
 * @property string|null $description
 * @property bool $is_active
 */
class Geofence extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'flespi_geofence_id',
        'name',
        'type',
        'geometry',
        'color',
        'description',
        'is_active',
    ];

    protected $casts = [
        'flespi_geofence_id' => 'integer',
        'geometry' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Check if geofence is a circle
     */
    public function isCircle(): bool
    {
        return $this->type === 'circle';
    }

    /**
     * Check if geofence is a polygon
     */
    public function isPolygon(): bool
    {
        return $this->type === 'polygon';
    }

    /**
     * Scope for active geofences
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get center coordinates for circle geofence
     */
    public function getCenterAttribute(): ?array
    {
        if ($this->isCircle() && isset($this->geometry['center'])) {
            return $this->geometry['center'];
        }
        return null;
    }

    /**
     * Get radius for circle geofence
     */
    public function getRadiusAttribute(): ?float
    {
        if ($this->isCircle() && isset($this->geometry['radius'])) {
            return $this->geometry['radius'];
        }
        return null;
    }

    /**
     * Get coordinates for polygon geofence
     */
    public function getCoordinatesAttribute(): ?array
    {
        if ($this->isPolygon() && isset($this->geometry['coordinates'])) {
            return $this->geometry['coordinates'];
        }
        return null;
    }
}
