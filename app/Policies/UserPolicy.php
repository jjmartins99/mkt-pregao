<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model)
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    public function create(User $user)
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model)
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    public function delete(User $user, User $model)
    {
        // Não permitir que um utilizador se elimine a si mesmo
        if ($user->id === $model->id) {
            return false;
        }

        return $user->isAdmin();
    }

    public function managePermissions(User $user)
    {
        return $user->isAdmin();
    }

    public function toggleStatus(User $user, User $model)
    {
        // Não permitir que um utilizador desative a sua própria conta
        if ($user->id === $model->id) {
            return false;
        }

        return $user->isAdmin();
    }
}