<?php

namespace App\Policies;

use App\Models\Donation;
use App\Models\User;
use App\UserRole;

class DonationPolicy
{
    /**
     * Адміністратори мають повний доступ
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return null;
    }

    /**
     * Користувач може переглядати список своїх донатів
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Користувач може переглядати свій донат
     */
    public function view(User $user, Donation $donation): bool
    {
        return $donation->user_id === $user->id;
    }

    /**
     * Будь-хто може створювати донати (навіть гості)
     */
    public function create(?User $user): bool
    {
        return true;
    }

    /**
     * Донати не можна редагувати
     */
    public function update(User $user, Donation $donation): bool
    {
        return false;
    }

    /**
     * Донати не можна видаляти
     */
    public function delete(User $user, Donation $donation): bool
    {
        return false;
    }

    /**
     * Тільки адміни
     */
    public function restore(User $user, Donation $donation): bool
    {
        return false;
    }

    /**
     * Тільки адміни
     */
    public function forceDelete(User $user, Donation $donation): bool
    {
        return false;
    }
}
