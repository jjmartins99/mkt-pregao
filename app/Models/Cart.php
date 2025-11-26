<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'store_id',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'total_amount',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relações
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    // Métodos
    public function calculateTotals()
    {
        $subtotal = $this->items->sum('total_price');
        $taxAmount = $subtotal * 0.14; // IVA 14%
        $totalAmount = $subtotal + $taxAmount + $this->shipping_cost;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    public function getItemsCountAttribute()
    {
        return $this->items->sum('quantity');
    }

    public function isEmpty()
    {
        return $this->items->isEmpty();
    }

    public function clear()
    {
        $this->items()->delete();
        $this->calculateTotals();
    }

    public function mergeWithSessionCart($sessionCart)
    {
        foreach ($sessionCart->items as $sessionItem) {
            $existingItem = $this->items()
                ->where('product_id', $sessionItem->product_id)
                ->first();

            if ($existingItem) {
                $existingItem->increment('quantity', $sessionItem->quantity);
            } else {
                $this->items()->create([
                    'product_id' => $sessionItem->product_id,
                    'quantity' => $sessionItem->quantity,
                    'unit_price' => $sessionItem->unit_price,
                    'total_price' => $sessionItem->total_price,
                ]);
            }
        }

        $this->calculateTotals();
        $sessionCart->clear();
    }
}