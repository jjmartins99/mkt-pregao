<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    const TYPE_ADMIN = 'admin';
    const TYPE_SELLER = 'seller';
    const TYPE_CUSTOMER = 'customer';
    const TYPE_DRIVER = 'driver';

    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'phone',
        'nif',
        'avatar',
        'is_active',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relações
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_users')
            ->withPivot('role', 'branch_id', 'is_active')
            ->withTimestamps();
    }

    public function driverProfile()
    {
        return $this->hasOne(Driver::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function stores()
    {
        return $this->hasMany(Store::class, 'owner_id');
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSellers($query)
    {
        return $query->where('type', self::TYPE_SELLER);
    }

    public function scopeCustomers($query)
    {
        return $query->where('type', self::TYPE_CUSTOMER);
    }

    public function scopeDrivers($query)
    {
        return $query->where('type', self::TYPE_DRIVER);
    }

    public function scopeAdmins($query)
    {
        return $query->where('type', self::TYPE_ADMIN);
    }

    // Métodos
    public function isAdmin()
    {
        return $this->type === self::TYPE_ADMIN;
    }

    public function isSeller()
    {
        return $this->type === self::TYPE_SELLER;
    }

    public function isCustomer()
    {
        return $this->type === self::TYPE_CUSTOMER;
    }

    public function isDriver()
    {
        return $this->type === self::TYPE_DRIVER;
    }

    public function hasPermission($permissionSlug)
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

    public function getAvatarUrlAttribute()
    {
        if (!$this->avatar) {
            return asset('images/default-avatar.png');
        }

        return Storage::disk('public')->url($this->avatar);
    }
}