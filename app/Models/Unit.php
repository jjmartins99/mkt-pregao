<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'symbol',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relações
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Métodos
    public function getFormattedNameAttribute()
    {
        return "{$this->name} ({$this->symbol})";
    }
}