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
        \App\Models\User::class => \App\Policies\UserPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        // Gates adicionais para empresas
        Gate::define('manage-company-users', function ($user, $company) {
            return app(\App\Policies\CompanyPolicy::class)->manageUsers($user, $company);
        });

        Gate::define('view-company-reports', function ($user, $company) {
            return app(\App\Policies\CompanyPolicy::class)->viewReports($user, $company);
        });

        Gate::define('access-company-backoffice', function ($user, $company) {
            return app(\App\Policies\CompanyPolicy::class)->accessBackoffice($user, $company);
        });

        // Gate para verificar se o utilizador é owner da empresa
        Gate::define('is-company-owner', function ($user, $company) {
            $companyUser = $company->users()->where('user_id', $user->id)->first();
            return $companyUser && $companyUser->pivot->role === 'owner';
        });

        // Gate para verificar se o utilizador é manager da empresa
        Gate::define('is-company-manager', function ($user, $company) {
            $companyUser = $company->users()->where('user_id', $user->id)->first();
            return $companyUser && in_array($companyUser->pivot->role, ['owner', 'manager']);
        });
    }
}