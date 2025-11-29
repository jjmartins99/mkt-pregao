<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true; // Todos podem ver produtos
    }

    public function view(User $user, Product $product)
    {
        return true; // Todos podem ver produto individual
    }

    public function create(User $user)
    {
        return $user->isSeller() || $user->isAdmin();
    }

    public function update(User $user, Product $product)
    {
        return $user->isAdmin() || $product->store->owner_id === $user->id;
    }

    public function delete(User $user, Product $product)
    {
        return $user->isAdmin() || $product->store->owner_id === $user->id;
    }

    public function manageStock(User $user, Product $product)
    {
        return $user->isAdmin() || $product->store->owner_id === $user->id;
    }

    public function manageImages(User $user, Product $product)
    {
        return $user->isAdmin() || $product->store->owner_id === $user->id;
    }
}