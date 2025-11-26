<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    const TYPE_IN = 'IN';
    const TYPE_OUT = 'OUT';
    const TYPE_ADJUSTMENT = 'ADJUSTMENT';
    const TYPE_TRANSFER_IN = 'TRANSFER_IN';
    const TYPE_TRANSFER_OUT = 'TRANSFER_OUT';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'batch_id',
        'type',
        'quantity',
        'balance',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'balance' => 'decimal:3',
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

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    // Escopos
    public function scopeIn($query)
    {
        return $query->whereIn('type', [self::TYPE_IN, self::TYPE_TRANSFER_IN]);
    }

    public function scopeOut($query)
    {
        return $query->whereIn('type', [self::TYPE_OUT, self::TYPE_TRANSFER_OUT]);
    }

    // Métodos
    public function isIn()
    {
        return in_array($this->type, [self::TYPE_IN, self::TYPE_TRANSFER_IN]);
    }

    public function isOut()
    {
        return in_array($this->type, [self::TYPE_OUT, self::TYPE_TRANSFER_OUT]);
    }
}