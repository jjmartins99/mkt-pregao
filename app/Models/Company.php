<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_INDIVIDUAL = 'individual';
    const TYPE_COLLECTIVE = 'collective';

    protected $fillable = [
        'name',
        'nif',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'postal_code',
        'logo',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relações
    public function users()
    {
        return $this->belongsToMany(User::class, 'company_users')
            ->withPivot('role', 'branch_id', 'is_active')
            ->withTimestamps();
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    public function mainBranch()
    {
        return $this->hasOne(Branch::class)->where('is_main', true);
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeIndividual($query)
    {
        return $query->where('type', self::TYPE_INDIVIDUAL);
    }

    public function scopeCollective($query)
    {
        return $query->where('type', self::TYPE_COLLECTIVE);
    }

    // Métodos
    public function getLogoUrlAttribute()
    {
        if (!$this->logo) {
            return asset('images/default-company.png');
        }

        return Storage::disk('public')->url($this->logo);
    }

    public function getActiveUsersCountAttribute()
    {
        return $this->users()->wherePivot('is_active', true)->count();
    }
}