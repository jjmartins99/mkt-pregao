<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Product::class => \App\Policies\ProductPolicy::class,
        \App\Models\Order::class => \App\Policies\OrderPolicy::class,
        \App\Models\Store::class => \App\Policies\StorePolicy::class,
        \App\Models\Company::class => \App\Policies\CompanyPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        // Gates adicionais
        Gate::define('manage-stock', function ($user, $product) {
            return $user->isAdmin() || $product->store->owner_id === $user->id;
        });

        Gate::define('view-reports', function ($user, $store = null) {
            if ($user->isAdmin()) return true;
            if ($store && $store->owner_id === $user->id) return true;
            return false;
        });
    }
}