<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeamPolicy
{
    /**
     * Тільки власник команди може її редагувати
     */
    public function update(User $user, Team $team): Response
    {
        return $team->isOwnedBy($user)
            ? Response::allow()
            : Response::deny('Ви не є власником цієї команди');
    }

    /**
     * Тільки власник команди може її видалити
     */
    public function delete(User $user, Team $team): Response
    {
        return $team->isOwnedBy($user)
            ? Response::allow()
            : Response::deny('Ви не є власником цієї команди');
    }
}
