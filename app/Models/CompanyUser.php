<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyUser extends Pivot
{
    use HasFactory;

    protected $table = 'company_users';

    protected $fillable = [
        'company_id',
        'user_id',
        'role',
        'branch_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relações
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Métodos
    public function isOwner()
    {
        return $this->role === 'owner';
    }

    public function isManager()
    {
        return $this->role === 'manager';
    }

    public function isEmployee()
    {
        return $this->role === 'employee';
    }

    public function getRoleNameAttribute()
    {
        $roles = [
            'owner' => 'Proprietário',
            'manager' => 'Gerente',
            'employee' => 'Funcionário',
        ];

        return $roles[$this->role] ?? $this->role;
    }
}