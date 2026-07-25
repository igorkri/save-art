<?php

namespace App\Observers;

use App\Models\User;
use App\Services\NotificationService;

class UserObserver
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    /**
     * Handle the User "updated" event.
     * Надсилаємо сповіщення при блокуванні/розблокуванні профілю.
     */
    public function updated(User $user): void
    {
        if (! $user->wasChanged('is_blocked')) {
            return;
        }

        if ($user->is_blocked) {
            $this->notificationService->notifyUserBlocked($user);
        } else {
            $this->notificationService->notifyUserUnblocked($user);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
