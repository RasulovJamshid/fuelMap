<?php

namespace App\Policies;

use App\Models\FuelStation;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class FuelStationPolicy
{
    use HandlesAuthorization;


    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\FuelStation  $fuelStation
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, FuelStation $fuelStation)
    {
        return $user->role == 'admin'
                ? Response::allow()
                : Response::deny('You do not have permissions to update.');

    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user)
    {
        return true;
    }


}
