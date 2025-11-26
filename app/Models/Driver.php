<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'user_id',
        'company_id',
        'driving_license',
        'license_photo',
        'status',
        'is_verified',
        'is_active',
        'rating',
        'total_ratings',
        'total_deliveries',
        'total_earnings',
        'metadata',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'rating' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relações
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'delivery_driver_id');
    }

    public function deliveryTracking()
    {
        return $this->hasMany(DeliveryTracking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeAvailable($query)
    {
        return $query->active()->verified();
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // Métodos
    public function isAvailable()
    {
        return $this->status === self::STATUS_ACTIVE && 
               $this->is_active && 
               $this->is_verified;
    }

    public function getLicensePhotoUrlAttribute()
    {
        return Storage::disk('public')->url($this->license_photo);
    }

    public function updateRating($newRating)
    {
        $totalRatings = $this->total_ratings;
        $currentRating = $this->rating;
        
        $newTotalRating = ($currentRating * $totalRatings) + $newRating;
        $this->total_ratings = $totalRatings + 1;
        $this->rating = $newTotalRating / $this->total_ratings;
        $this->save();
    }

    public function incrementDeliveries()
    {
        $this->increment('total_deliveries');
    }

    public function addEarnings($amount)
    {
        $this->increment('total_earnings', $amount);
    }

    public function getCurrentLocationAttribute()
    {
        $latestTracking = $this->deliveryTracking()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest()
            ->first();

        return $latestTracking ? [
            'latitude' => $latestTracking->latitude,
            'longitude' => $latestTracking->longitude,
            'address' => $latestTracking->location_address,
            'updated_at' => $latestTracking->updated_at
        ] : null;
    }
}