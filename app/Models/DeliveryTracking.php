<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryTracking extends Model
{
    use HasFactory;

    const STATUS_ASSIGNED = 'assigned';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_ON_ROUTE = 'on_route';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';

    protected $table = 'delivery_tracking';

    protected $fillable = [
        'order_id',
        'driver_id',
        'latitude',
        'longitude',
        'location_address',
        'status',
        'notes',
        'status_changed_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'status_changed_at' => 'datetime',
    ];

    // Relações
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    // Escopos
    public function scopeCurrentLocation($query)
    {
        return $query->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->latest();
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Métodos
    public function updateLocation($latitude, $longitude, $address = null)
    {
        $this->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location_address' => $address,
        ]);
    }

    public function updateStatus($status, $notes = null)
    {
        $this->update([
            'status' => $status,
            'notes' => $notes,
            'status_changed_at' => now(),
        ]);
    }

    public function getCoordinatesAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return [
                'lat' => $this->latitude,
                'lng' => $this->longitude
            ];
        }

        return null;
    }
}