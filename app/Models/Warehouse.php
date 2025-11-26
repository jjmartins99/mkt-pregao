<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_MAIN = 'main';
    const TYPE_SECONDARY = 'secondary';
    const TYPE_TRANSIT = 'transit';
    const TYPE_QUARANTINE = 'quarantine';

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'code',
        'address',
        'city',
        'country',
        'postal_code',
        'contact_person',
        'contact_phone',
        'area',
        'type',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    // Relações
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMain($query)
    {
        return $query->where('type', self::TYPE_MAIN);
    }

    // Métodos
    public function getTotalProductsAttribute()
    {
        return $this->stocks()->where('quantity', '>', 0)->count();
    }

    public function getTotalValueAttribute()
    {
        return $this->stocks()->join('products', 'stocks.product_id', '=', 'products.id')
            ->join('product_prices', function ($join) {
                $join->on('products.id', '=', 'product_prices.product_id')
                    ->where('product_prices.is_active', true);
            })
            ->sum(\DB::raw('stocks.quantity * product_prices.cost_price'));
    }
}