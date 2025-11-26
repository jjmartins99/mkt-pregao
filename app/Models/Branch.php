<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'phone',
        'email',
        'address',
        'city',
        'country',
        'postal_code',
        'is_active',
        'is_main',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_main' => 'boolean',
        'settings' => 'array',
    ];

    // Relações
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function productPrices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function companyUsers()
    {
        return $this->hasMany(CompanyUser::class);
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    // Métodos
    public function getMainWarehouseAttribute()
    {
        return $this->warehouses()->where('type', 'main')->first();
    }
}