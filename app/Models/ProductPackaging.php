<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPackaging extends Model
{
    use HasFactory;

    protected $table = 'product_packaging';

    protected $fillable = [
        'product_id',
        'name',
        'barcode',
        'conversion_factor',
        'price',
        'weight',
        'min_quantity',
        'max_quantity',
        'is_active',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:3',
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relações
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Métodos
    public function calculateBaseQuantity($packagedQuantity)
    {
        return $packagedQuantity * $this->conversion_factor;
    }

    public function calculatePackagedQuantity($baseQuantity)
    {
        return $baseQuantity / $this->conversion_factor;
    }
}