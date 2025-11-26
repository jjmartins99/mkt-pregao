<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    // Relações
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Métodos
    public function getLogoUrlAttribute()
    {
        if (!$this->logo) {
            return asset('images/default-brand.png');
        }

        return Storage::disk('public')->url($this->logo);
    }

    public function getProductsCountAttribute()
    {
        return $this->products()->active()->count();
    }
}