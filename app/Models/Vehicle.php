<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    const TYPE_CAR = 'car';
    const TYPE_MOTORCYCLE = 'motorcycle';
    const TYPE_BICYCLE = 'bicycle';
    const TYPE_TRUCK = 'truck';
    const TYPE_VAN = 'van';

    protected $fillable = [
        'driver_id',
        'make',
        'model',
        'year',
        'color',
        'plate_number',
        'type',
        'capacity_kg',
        'capacity_volume',
        'insurance_number',
        'insurance_expiry',
        'vehicle_photo',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'capacity_kg' => 'decimal:2',
        'capacity_volume' => 'decimal:2',
        'insurance_expiry' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    // Relações
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Métodos
    public function getVehiclePhotoUrlAttribute()
    {
        if (!$this->vehicle_photo) {
            return asset('images/default-vehicle.png');
        }

        return Storage::disk('public')->url($this->vehicle_photo);
    }

    public function getFullModelAttribute()
    {
        return "{$this->make} {$this->model} ({$this->year})";
    }

    public function isInsuranceExpired()
    {
        return $this->insurance_expiry && $this->insurance_expiry->isPast();
    }

    public function getInsuranceStatusAttribute()
    {
        if (!$this->insurance_expiry) {
            return 'no_insurance';
        }

        if ($this->isInsuranceExpired()) {
            return 'expired';
        }

        if ($this->insurance_expiry->diffInDays(now()) <= 30) {
            return 'expiring_soon';
        }

        return 'valid';
    }
}