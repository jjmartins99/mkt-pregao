<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'min_stock',
        'max_stock',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'reserved_quantity' => 'decimal:3',
        'available_quantity' => 'decimal:3',
        'min_stock' => 'decimal:3',
        'max_stock' => 'decimal:3',
    ];

    // Relações
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // Métodos
    public function updateAvailableQuantity()
    {
        $this->available_quantity = $this->quantity - $this->reserved_quantity;
        $this->save();
    }

    public function isBelowMinimum()
    {
        return $this->available_quantity < $this->min_stock;
    }

    public function isAboveMaximum()
    {
        return $this->max_stock && $this->available_quantity > $this->max_stock;
    }

    public function getStockStatusAttribute()
    {
        if ($this->available_quantity <= 0) {
            return 'out_of_stock';
        } elseif ($this->isBelowMinimum()) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }
}