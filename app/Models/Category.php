<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'order',
        'is_active',
        'is_featured',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'metadata' => 'array',
    ];

    // Relações
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithChildren($query)
    {
        return $query->with(['children' => function ($q) {
            $q->active()->orderBy('order');
        }]);
    }

    // Métodos
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return asset('images/default-category.png');
        }

        return Storage::disk('public')->url($this->image);
    }

    public function getProductsCountAttribute()
    {
        return $this->products()->active()->count();
    }

    public function getAllChildrenIds()
    {
        $ids = $this->children->pluck('id')->toArray();
        
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getAllChildrenIds());
        }
        
        return $ids;
    }
}