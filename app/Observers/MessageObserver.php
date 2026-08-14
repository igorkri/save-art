<?php

namespace App\Observers;

use App\Models\Message;
use App\Services\NotificationService;

class MessageObserver
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle the Message "created" event.
     * Надсилаємо сповіщення користувачу, коли адміністрація (або система) пише йому повідомлення.
     */
    public function created(Message $message): void
    {
        if (! $message->isFromAdmin()) {
            return;
        }

        $this->notificationService->notifyNewMessage($message);
    }
}
