<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'module',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relações
    public function users()
    {
        return $this->belongsToMany(User::class, 'permission_user');
    }

    // Escopos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    // Métodos
    public static function getModules()
    {
        return [
            'users' => 'Gestão de Utilizadores',
            'products' => 'Gestão de Produtos',
            'orders' => 'Gestão de Pedidos',
            'stocks' => 'Gestão de Stocks',
            'stores' => 'Gestão de Lojas',
            'drivers' => 'Gestão de Motoristas',
            'companies' => 'Gestão de Empresas',
            'reports' => 'Relatórios',
            'settings' => 'Configurações',
            'financial' => 'Financeiro',
        ];
    }

    public function getModuleNameAttribute()
    {
        $modules = self::getModules();
        return $modules[$this->module] ?? $this->module;
    }

    public static function createDefaultPermissions()
    {
        $permissions = [
            // Users module
            ['name' => 'Ver Utilizadores', 'slug' => 'users.view', 'module' => 'users'],
            ['name' => 'Criar Utilizadores', 'slug' => 'users.create', 'module' => 'users'],
            ['name' => 'Editar Utilizadores', 'slug' => 'users.edit', 'module' => 'users'],
            ['name' => 'Eliminar Utilizadores', 'slug' => 'users.delete', 'module' => 'users'],
            
            // Products module
            ['name' => 'Ver Produtos', 'slug' => 'products.view', 'module' => 'products'],
            ['name' => 'Criar Produtos', 'slug' => 'products.create', 'module' => 'products'],
            ['name' => 'Editar Produtos', 'slug' => 'products.edit', 'module' => 'products'],
            ['name' => 'Eliminar Produtos', 'slug' => 'products.delete', 'module' => 'products'],
            ['name' => 'Gerir Stocks', 'slug' => 'stocks.manage', 'module' => 'products'],
            
            // Orders module
            ['name' => 'Ver Pedidos', 'slug' => 'orders.view', 'module' => 'orders'],
            ['name' => 'Criar Pedidos', 'slug' => 'orders.create', 'module' => 'orders'],
            ['name' => 'Editar Pedidos', 'slug' => 'orders.edit', 'module' => 'orders'],
            ['name' => 'Cancelar Pedidos', 'slug' => 'orders.cancel', 'module' => 'orders'],
            ['name' => 'Processar Pedidos', 'slug' => 'orders.process', 'module' => 'orders'],
            
            // Stores module
            ['name' => 'Ver Lojas', 'slug' => 'stores.view', 'module' => 'stores'],
            ['name' => 'Criar Lojas', 'slug' => 'stores.create', 'module' => 'stores'],
            ['name' => 'Editar Lojas', 'slug' => 'stores.edit', 'module' => 'stores'],
            ['name' => 'Verificar Lojas', 'slug' => 'stores.verify', 'module' => 'stores'],
            
            // Drivers module
            ['name' => 'Ver Motoristas', 'slug' => 'drivers.view', 'module' => 'drivers'],
            ['name' => 'Criar Motoristas', 'slug' => 'drivers.create', 'module' => 'drivers'],
            ['name' => 'Editar Motoristas', 'slug' => 'drivers.edit', 'module' => 'drivers'],
            ['name' => 'Verificar Motoristas', 'slug' => 'drivers.verify', 'module' => 'drivers'],
            
            // Financial module
            ['name' => 'Ver Transações', 'slug' => 'transactions.view', 'module' => 'financial'],
            ['name' => 'Processar Pagamentos', 'slug' => 'payments.process', 'module' => 'financial'],
            ['name' => 'Gerir Comissões', 'slug' => 'commissions.manage', 'module' => 'financial'],
            
            // Reports module
            ['name' => 'Ver Relatórios', 'slug' => 'reports.view', 'module' => 'reports'],
            ['name' => 'Exportar Dados', 'slug' => 'reports.export', 'module' => 'reports'],
            
            // Settings module
            ['name' => 'Gerir Configurações', 'slug' => 'settings.manage', 'module' => 'settings'],
        ];

        foreach ($permissions as $permission) {
            self::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}