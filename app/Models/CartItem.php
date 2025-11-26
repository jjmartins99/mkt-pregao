<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relações
    public function cart()
    {
        return $this->belongsTo(Cart::class);
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

    public function updateQuantity($quantity)
    {
        $this->update(['quantity' => $quantity]);
        $this->calculateTotal();
        $this->cart->calculateTotals();
    }

    public function incrementQuantity($amount = 1)
    {
        $this->increment('quantity', $amount);
        $this->calculateTotal();
        $this->cart->calculateTotals();
    }

    public function decrementQuantity($amount = 1)
    {
        if ($this->quantity > $amount) {
            $this->decrement('quantity', $amount);
            $this->calculateTotal();
            $this->cart->calculateTotals();
        }
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