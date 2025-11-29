<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true; // Cada user vê apenas seus pedidos (escopo no controller)
    }

    public function view(User $user, Order $order)
    {
        return $user->isAdmin() || 
               $order->customer_id === $user->id ||
               $order->store->owner_id === $user->id ||
               ($user->isDriver() && $order->delivery_driver_id === $user->driverProfile->id);
    }

    public function create(User $user)
    {
        return $user->isCustomer();
    }

    public function update(User $user, Order $order)
    {
        // Apenas admin, dono da loja ou motorista atribuído podem atualizar
        return $user->isAdmin() || 
               $order->store->owner_id === $user->id ||
               ($user->isDriver() && $order->delivery_driver_id === $user->driverProfile->id);
    }

    public function cancel(User $user, Order $order)
    {
        // Cliente pode cancelar apenas pedidos pendentes
        if ($order->customer_id === $user->id) {
            return $order->canBeCancelled();
        }

        // Admin ou dono da loja podem cancelar em qualquer estado
        return $user->isAdmin() || $order->store->owner_id === $user->id;
    }

    public function updateStatus(User $user, Order $order)
    {
        return $user->isAdmin() || 
               $order->store->owner_id === $user->id ||
               ($user->isDriver() && $order->delivery_driver_id === $user->driverProfile->id);
    }
}