<?php

namespace App\Policies;

use App\Models\{Machine, User};

class MachinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('machines.view');
    }

    public function view(User $user, Machine $machine): bool
    {
        return $user->center_id === $machine->center_id
            && $user->can('machines.view');
    }

    public function create(User $user): bool
    {
        return $user->can('machines.create');
    }

    public function update(User $user, Machine $machine): bool
    {
        return $user->center_id === $machine->center_id
            && $user->can('machines.update');
    }

    public function delete(User $user, Machine $machine): bool
    {
        return $user->center_id === $machine->center_id
            && $user->can('machines.delete');
    }
}
