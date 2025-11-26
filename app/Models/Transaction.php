<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_SALE = 'sale';
    const TYPE_REFUND = 'refund';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_COMMISSION = 'commission';
    const TYPE_PAYMENT = 'payment';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'transaction_number',
        'order_id',
        'user_id',
        'type',
        'status',
        'amount',
        'fee',
        'net_amount',
        'currency',
        'payment_gateway',
        'gateway_transaction_id',
        'description',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    // Relações
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Escopos
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSales($query)
    {
        return $query->where('type', self::TYPE_SALE);
    }

    public function scopeRefunds($query)
    {
        return $query->where('type', self::TYPE_REFUND);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    // Métodos
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed()
    {
        $this->update([
            'status' => self::STATUS_FAILED,
        ]);
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2, ',', ' ') . ' ' . $this->currency;
    }

    public function getFormattedNetAmountAttribute()
    {
        return number_format($this->net_amount, 2, ',', ' ') . ' ' . $this->currency;
    }

    public function isPositive()
    {
        return in_array($this->type, [self::TYPE_SALE, self::TYPE_PAYMENT]);
    }

    public function isNegative()
    {
        return in_array($this->type, [self::TYPE_REFUND, self::TYPE_WITHDRAWAL, self::TYPE_COMMISSION]);
    }

    public function getAmountWithSignAttribute()
    {
        $sign = $this->isPositive() ? '+' : '-';
        return $sign . ' ' . $this->getFormattedAmountAttribute();
    }
}