<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image_path',
        'is_primary',
        'order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    // Relações
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Métodos
    public function getImageUrlAttribute()
    {
        return Storage::disk('public')->url($this->image_path);
    }
}