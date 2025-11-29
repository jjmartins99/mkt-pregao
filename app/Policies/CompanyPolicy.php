<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Company;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determinar se o utilizador pode ver qualquer empresa.
     */
    public function viewAny(User $user)
    {
        // Admin pode ver todas as empresas
        // Sellers só podem ver empresas onde estão associados
        return $user->isAdmin() || $user->isSeller();
    }

    /**
     * Determinar se o utilizador pode ver uma empresa específica.
     */
    public function view(User $user, Company $company)
    {
        // Admin pode ver qualquer empresa
        if ($user->isAdmin()) {
            return true;
        }

        // Sellers só podem ver empresas onde estão associados
        if ($user->isSeller()) {
            return $company->users()->where('user_id', $user->id)->exists();
        }

        // Drivers só podem ver empresas onde estão associados
        if ($user->isDriver()) {
            return $company->users()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Determinar se o utilizador pode criar empresas.
     */
    public function create(User $user)
    {
        // Apenas admin e sellers podem criar empresas
        return $user->isAdmin() || $user->isSeller();
    }

    /**
     * Determinar se o utilizador pode atualizar a empresa.
     */
    public function update(User $user, Company $company)
    {
        // Admin pode atualizar qualquer empresa
        if ($user->isAdmin()) {
            return true;
        }

        // Sellers só podem atualizar empresas onde são owners ou managers
        if ($user->isSeller()) {
            $companyUser = $company->users()->where('user_id', $user->id)->first();
            return $companyUser && in_array($companyUser->pivot->role, ['owner', 'manager']);
        }

        return false;
    }

    /**
     * Determinar se o utilizador pode eliminar a empresa.
     */
    public function delete(User $user, Company $company)
    {
        // Apenas admin pode eliminar empresas
        // Empresas com dados associados não devem ser eliminadas
        if (!$user->isAdmin()) {
            return false;
        }

        // Verificar se a empresa tem dados associados
        return !$this->companyHasAssociatedData($company);
    }

    /**
     * Determinar se o utilizador pode restaurar a empresa (soft delete).
     */
    public function restore(User $user, Company $company)
    {
        return $user->isAdmin();
    }

    /**
     * Determinar se o utilizador pode eliminar permanentemente a empresa.
     */
    public function forceDelete(User $user, Company $company)
    {
        return $user->isAdmin() && !$this->companyHasAssociatedData($company);
    }

    /**
     * Determinar se o utilizador pode gerir utilizadores da empresa.
     */
    public function manageUsers(User $user, Company $company)
    {
        // Admin pode gerir utilizadores de qualquer empresa
        if ($user->isAdmin()) {
            return true;
        }

        // Sellers só podem gerir utilizadores em empresas onde são owners ou managers
        if ($user->isSeller()) {
            $companyUser = $company->users()->where('user_id', $user->id)->first();
            return $companyUser && in_array($companyUser->pivot->role, ['owner', 'manager']);
        }

        return false;
    }

    /**
     * Determinar se o utilizador pode adicionar utilizadores à empresa.
     */
    public function addUser(User $user, Company $company)
    {
        return $this->manageUsers($user, $company);
    }

    /**
     * Determinar se o utilizador pode remover utilizadores da empresa.
     */
    public function removeUser(User $user, Company $company)
    {
        return $this->manageUsers($user, $company);
    }

    /**
     * Determinar se o utilizador pode atualizar funções de utilizadores.
     */
    public function updateUserRole(User $user, Company $company)
    {
        // Apenas owners e admin podem atualizar funções
        if ($user->isAdmin()) {
            return true;
        }

        $companyUser = $company->users()->where('user_id', $user->id)->first();
        return $companyUser && $companyUser->pivot->role === 'owner';
    }

    /**
     * Determinar se o utilizador pode gerir filiais da empresa.
     */
    public function manageBranches(User $user, Company $company)
    {
        return $this->manageUsers($user, $company);
    }

    /**
     * Determinar se o utilizador pode gerir armazéns da empresa.
     */
    public function manageWarehouses(User $user, Company $company)
    {
        return $this->manageUsers($user, $company);
    }

    /**
     * Determinar se o utilizador pode gerir lojas da empresa.
     */
    public function manageStores(User $user, Company $company)
    {
        return $this->manageUsers($user, $company);
    }

    /**
     * Determinar se o utilizador pode ver relatórios da empresa.
     */
    public function viewReports(User $user, Company $company)
    {
        // Admin pode ver relatórios de qualquer empresa
        if ($user->isAdmin()) {
            return true;
        }

        // Sellers só podem ver relatórios de empresas onde estão associados
        if ($user->isSeller()) {
            return $company->users()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Determinar se o utilizador pode ativar/desativar a empresa.
     */
    public function toggleStatus(User $user, Company $company)
    {
        return $user->isAdmin();
    }

    /**
     * Determinar se o utilizador pode ver dados financeiros da empresa.
     */
    public function viewFinancialData(User $user, Company $company)
    {
        // Apenas admin e owners podem ver dados financeiros
        if ($user->isAdmin()) {
            return true;
        }

        $companyUser = $company->users()->where('user_id', $user->id)->first();
        return $companyUser && $companyUser->pivot->role === 'owner';
    }

    /**
     * Determinar se o utilizador pode exportar dados da empresa.
     */
    public function exportData(User $user, Company $company)
    {
        return $this->viewReports($user, $company);
    }

    /**
     * Verificar se a empresa tem dados associados que impedem a eliminação.
     */
    private function companyHasAssociatedData(Company $company)
    {
        return $company->stores()->exists() ||
               $company->branches()->exists() ||
               $company->warehouses()->exists() ||
               $company->drivers()->exists() ||
               $company->users()->exists();
    }

    /**
     * Determinar se o utilizador pode transferir propriedade da empresa.
     */
    public function transferOwnership(User $user, Company $company)
    {
        // Apenas o owner atual pode transferir a propriedade
        $companyUser = $company->users()->where('user_id', $user->id)->first();
        return $companyUser && $companyUser->pivot->role === 'owner';
    }

    /**
     * Determinar se o utilizador pode ver o dashboard da empresa.
     */
    public function viewDashboard(User $user, Company $company)
    {
        return $this->view($user, $company);
    }

    /**
     * Determinar se o utilizador pode configurar a empresa.
     */
    public function configure(User $user, Company $company)
    {
        return $this->update($user, $company);
    }

    /**
     * Determinar se o utilizador pode ver estatísticas da empresa.
     */
    public function viewStatistics(User $user, Company $company)
    {
        return $this->viewReports($user, $company);
    }

    /**
     * Determinar se o utilizador pode aceder ao backoffice da empresa.
     */
    public function accessBackoffice(User $user, Company $company)
    {
        return $this->view($user, $company) && 
               ($user->isAdmin() || $user->isSeller());
    }
}