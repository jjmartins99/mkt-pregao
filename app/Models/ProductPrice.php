<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'branch_id',
        'price',
        'compare_price',
        'cost_price',
        'is_active',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    // Relações
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('valid_to')->orWhere('valid_to', '>=', $now);
        });
    }

    // Métodos
    public function getDiscountPercentageAttribute()
    {
        if (!$this->compare_price || $this->compare_price <= $this->price) {
            return 0;
        }

        return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 2);
    }

    public function isOnSale()
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }
}