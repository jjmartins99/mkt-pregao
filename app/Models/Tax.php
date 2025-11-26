<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'is_active',
        'is_default',
        'metadata',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'metadata' => 'array',
    ];

    // Relações
    public function taxRates()
    {
        return $this->hasMany(TaxRate::class);
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Métodos
    public function calculateTax($amount)
    {
        return $amount * ($this->rate / 100);
    }

    public function getFormattedRateAttribute()
    {
        return $this->rate . '%';
    }

    public function makeDefault()
    {
        // Remove default from other taxes
        self::where('is_default', true)->update(['is_default' => false]);
        
        $this->update(['is_default' => true]);
    }

    public function getApplicableRate($country = 'Angola', $state = null, $city = null, $postalCode = null)
    {
        $rate = $this->taxRates()
            ->where('country', $country)
            ->when($state, function ($q) use ($state) {
                $q->where('state', $state);
            })
            ->when($city, function ($q) use ($city) {
                $q->where('city', $city);
            })
            ->when($postalCode, function ($q) use ($postalCode) {
                $q->where('postal_code', $postalCode);
            })
            ->where('is_active', true)
            ->first();

        return $rate ? $rate->rate : $this->rate;
    }
}