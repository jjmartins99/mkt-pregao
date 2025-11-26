<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_id',
        'country',
        'state',
        'city',
        'postal_code',
        'rate',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relações
    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLocation($query, $country, $state = null, $city = null, $postalCode = null)
    {
        return $query->where('country', $country)
            ->when($state, function ($q) use ($state) {
                $q->where('state', $state);
            })
            ->when($city, function ($q) use ($city) {
                $q->where('city', $city);
            })
            ->when($postalCode, function ($q) use ($postalCode) {
                $q->where('postal_code', $postalCode);
            });
    }

    // Métodos
    public function getFormattedRateAttribute()
    {
        return $this->rate . '%';
    }

    public function getLocationDescriptionAttribute()
    {
        $parts = array_filter([
            $this->country,
            $this->state,
            $this->city,
            $this->postal_code,
        ]);

        return implode(', ', $parts);
    }
}