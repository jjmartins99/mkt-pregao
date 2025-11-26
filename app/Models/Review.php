<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'product_id',
        'store_id',
        'driver_id',
        'rating',
        'comment',
        'response',
        'responded_at',
        'is_approved',
        'is_visible',
        'metadata',
    ];

    protected $casts = [
        'rating' => 'integer',
        'responded_at' => 'datetime',
        'is_approved' => 'boolean',
        'is_visible' => 'boolean',
        'metadata' => 'array',
    ];

    // Relações
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    // Escopos
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeProductReviews($query)
    {
        return $query->whereNotNull('product_id');
    }

    public function scopeStoreReviews($query)
    {
        return $query->whereNotNull('store_id');
    }

    public function scopeDriverReviews($query)
    {
        return $query->whereNotNull('driver_id');
    }

    public function scopeWithResponse($query)
    {
        return $query->whereNotNull('response');
    }

    public function scopeHighRating($query, $minRating = 4)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeLowRating($query, $maxRating = 2)
    {
        return $query->where('rating', '<=', $maxRating);
    }

    // Métodos
    public function isApproved()
    {
        return $this->is_approved;
    }

    public function isVisible()
    {
        return $this->is_visible;
    }

    public function hasResponse()
    {
        return !is_null($this->response);
    }

    public function approve()
    {
        $this->update([
            'is_approved' => true,
            'is_visible' => true,
        ]);

        $this->updateRelatedRating();
    }

    public function reject()
    {
        $this->update([
            'is_approved' => false,
            'is_visible' => false,
        ]);
    }

    public function addResponse($response, $userId = null)
    {
        $this->update([
            'response' => $response,
            'responded_at' => now(),
        ]);
    }

    private function updateRelatedRating()
    {
        if ($this->product_id) {
            $this->product->updateRating();
        }

        if ($this->store_id) {
            $this->store->update([
                'rating' => $this->store->reviews()->approved()->avg('rating') ?? 0,
                'total_reviews' => $this->store->reviews()->approved()->count(),
            ]);
        }

        if ($this->driver_id) {
            $this->driver->updateRating($this->rating);
        }
    }

    public function getReviewTypeAttribute()
    {
        if ($this->product_id) return 'product';
        if ($this->store_id) return 'store';
        if ($this->driver_id) return 'driver';
        return 'general';
    }

    public function getRatingStarsAttribute()
    {
        return str_repeat('⭐', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }
}