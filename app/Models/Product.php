<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    const KIND_GOOD = 'good';
    const KIND_SERVICE = 'service';

    const PICKING_FIFO = 'fifo';
    const PICKING_LIFO = 'lifo';
    const PICKING_FEFO = 'fefo';

    protected $fillable = [
        'store_id',
        'name',
        'description',
        'sku',
        'barcode',
        'kind',
        'category_id',
        'brand_id',
        'unit_id',
        'weight',
        'dimensions',
        'track_stock',
        'is_active',
        'requires_expiry',
        'requires_batch',
        'picking_policy',
        'min_stock',
        'max_stock',
        'rating',
        'total_reviews',
        'total_sold',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'track_stock' => 'boolean',
        'requires_expiry' => 'boolean',
        'requires_batch' => 'boolean',
        'dimensions' => 'array',
        'weight' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'max_stock' => 'decimal:2',
        'rating' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relações
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function packaging()
    {
        return $this->hasMany(ProductPackaging::class);
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
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

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGoods($query)
    {
        return $query->where('kind', self::KIND_GOOD);
    }

    public function scopeServices($query)
    {
        return $query->where('kind', self::KIND_SERVICE);
    }

    public function scopeWithStock($query, $warehouseId = null)
    {
        return $query->whereHas('stocks', function ($q) use ($warehouseId) {
            if ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }
            $q->where('available_quantity', '>', 0);
        });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeBestSelling($query)
    {
        return $query->orderBy('total_sold', 'desc');
    }

    public function scopeTopRated($query)
    {
        return $query->orderBy('rating', 'desc');
    }

    // Métodos
    public function isGood()
    {
        return $this->kind === self::KIND_GOOD;
    }

    public function isService()
    {
        return $this->kind === self::KIND_SERVICE;
    }

    public function getCurrentPrice($branchId = null)
    {
        $price = $this->prices()
            ->where('is_active', true)
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('price', 'asc')
            ->first();

        return $price ? $price->price : 0;
    }

    public function getCurrentStock($warehouseId)
    {
        if (!$this->track_stock) {
            return 9999; // Stock ilimitado para serviços
        }

        $stock = $this->stocks()
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $stock ? $stock->available_quantity : 0;
    }

    public function getPrimaryImageAttribute()
    {
        return $this->images()->where('is_primary', true)->first() ?? 
               $this->images()->first();
    }

    public function getImageUrlAttribute()
    {
        $primaryImage = $this->primaryImage;
        return $primaryImage ? Storage::disk('public')->url($primaryImage->image_path) : 
               asset('images/default-product.png');
    }

    public function incrementSold($quantity = 1)
    {
        $this->increment('total_sold', $quantity);
    }

    public function updateRating()
    {
        $reviews = $this->reviews()->where('is_approved', true);
        $this->rating = $reviews->avg('rating') ?? 0;
        $this->total_reviews = $reviews->count();
        $this->save();
    }
}