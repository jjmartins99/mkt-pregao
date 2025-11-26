<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_description',
        'sku',
        'quantity',
        'unit_price',
        'total_price',
        'tax_amount',
        'discount_amount',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relações
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Métodos
    public function calculateTotal()
    {
        $this->total_price = $this->quantity * $this->unit_price;
        $this->save();
    }

    public function getFormattedUnitPriceAttribute()
    {
        return number_format($this->unit_price, 2, ',', ' ') . ' Kz';
    }

    public function getFormattedTotalPriceAttribute()
    {
        return number_format($this->total_price, 2, ',', ' ') . ' Kz';
    }
}