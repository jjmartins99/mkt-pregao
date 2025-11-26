<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_FAILED = 'failed';
    const PAYMENT_STATUS_REFUNDED = 'refunded';
    const PAYMENT_STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_CARD = 'card';
    const PAYMENT_METHOD_TRANSFER = 'transfer';
    const PAYMENT_METHOD_MOBILE = 'mobile';

    protected $fillable = [
        'order_number',
        'customer_id',
        'store_id',
        'branch_id',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'discount_amount',
        'total_amount',
        'notes',
        'delivery_address',
        'billing_address',
        'invoice_number',
        'delivery_driver_id',
        'estimated_delivery_time',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'delivery_address' => 'array',
        'billing_address' => 'array',
        'estimated_delivery_time' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relações
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'delivery_driver_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function deliveryTracking()
    {
        return $this->hasOne(DeliveryTracking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Escopos
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::PAYMENT_STATUS_PAID);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    // Métodos
    public function calculateTotals()
    {
        $subtotal = $this->items->sum('total_price');
        $taxAmount = $subtotal * 0.14; // IVA 14%
        $totalAmount = $subtotal + $taxAmount + $this->shipping_cost - $this->discount_amount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    public function isPaid()
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }

    public function isDelivered()
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function markAsPaid()
    {
        $this->update([
            'payment_status' => self::PAYMENT_STATUS_PAID,
        ]);
    }

    public function markAsDelivered()
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function addStatusHistory($status, $notes = null, $userId = null)
    {
        $this->statusHistory()->create([
            'status' => $status,
            'notes' => $notes,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    public function getDeliveryAddressStringAttribute()
    {
        $address = $this->delivery_address;
        if (!$address) return '';

        return implode(', ', array_filter([
            $address['street'] ?? '',
            $address['city'] ?? '',
            $address['province'] ?? '',
            $address['postal_code'] ?? '',
        ]));
    }

    public function getItemsCountAttribute()
    {
        return $this->items->sum('quantity');
    }
}