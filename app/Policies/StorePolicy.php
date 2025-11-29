<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Store;
use Illuminate\Auth\Access\HandlesAuthorization;

class StorePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true; // Todos podem ver lista de lojas
    }

    public function view(User $user, Store $store)
    {
        return true; // Todos podem ver loja individual
    }

    public function create(User $user)
    {
        return $user->isSeller() || $user->isAdmin();
    }

    public function update(User $user, Store $store)
    {
        return $user->isAdmin() || $store->owner_id === $user->id;
    }

    public function delete(User $user, Store $store)
    {
        return $user->isAdmin() || $store->owner_id === $user->id;
    }

    public function manageProducts(User $user, Store $store)
    {
        return $user->isAdmin() || $store->owner_id === $user->id;
    }

    public function viewReports(User $user, Store $store)
    {
        return $user->isAdmin() || $store->owner_id === $user->id;
    }
}