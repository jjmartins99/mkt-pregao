<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'owner_id',
        'name',
        'slug',
        'description',
        'logo',
        'banner',
        'phone',
        'email',
        'address',
        'city',
        'country',
        'postal_code',
        'is_active',
        'is_verified',
        'rating',
        'total_reviews',
        'settings',
        'business_hours',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'rating' => 'decimal:2',
        'settings' => 'array',
        'business_hours' => 'array',
    ];

    // Relações
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function warehouses()
    {
        return $this->hasManyThrough(Warehouse::class, Company::class, 'id', 'company_id', 'company_id');
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Métodos
    public function getLogoUrlAttribute()
    {
        if (!$this->logo) {
            return asset('images/default-store.png');
        }

        return Storage::disk('public')->url($this->logo);
    }

    public function getBannerUrlAttribute()
    {
        if (!$this->banner) {
            return asset('images/default-banner.jpg');
        }

        return Storage::disk('public')->url($this->banner);
    }

    public function getActiveProductsCountAttribute()
    {
        return $this->products()->active()->count();
    }

    public function getTotalSalesAttribute()
    {
        return $this->orders()->where('status', Order::STATUS_DELIVERED)->count();
    }

    public function isOpen()
    {
        $businessHours = $this->business_hours ?? [];
        $currentDay = strtolower(now()->format('l'));
        $currentTime = now()->format('H:i');

        if (!isset($businessHours[$currentDay]) || !$businessHours[$currentDay]['open']) {
            return false;
        }

        return $currentTime >= $businessHours[$currentDay]['open_time'] && 
               $currentTime <= $businessHours[$currentDay]['close_time'];
    }
}