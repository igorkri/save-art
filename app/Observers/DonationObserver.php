<?php

namespace App\Observers;

use App\Enums\NotificationType;
use App\Models\Donation;
use App\Models\Notification;
use App\Services\NotificationService;

class DonationObserver
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle the Donation "updated" event.
     * Надсилаємо сповіщення коли донат оплачено
     */
    public function updated(Donation $donation): void
    {
        // Перевіряємо чи змінився статус на 'paid'
        if ($donation->wasChanged('status') && $donation->status === 'paid') {
            // Сповіщення автору проєкту
            $this->notificationService->notifyDonationReceived($donation);

            // Сповіщення донатеру
            $this->notificationService->notifyDonationMade($donation);

            // Перевіряємо чи проект досяг мети
            $this->checkFundingComplete($donation);
        }
    }

    /**
     * Перевірити чи проект досяг мети збору
     */
    private function checkFundingComplete(Donation $donation): void
    {
        $project = $donation->project;

        if (! $project) {
            return;
        }

        // Перевіряємо чи досягнуто мету
        $totalCollected = $project->donations()
            ->where('status', 'paid')
            ->sum('amount');

        if ($totalCollected < $project->budget_goal) {
            return;
        }

        // Перевіряємо чи вже надсилали сповіщення про завершення збору
        $alreadyNotified = Notification::where('user_id', $project->user_id)
            ->where('type', NotificationType::ProjectFundingComplete)
            ->whereJsonContains('data->project_id', $project->id)
            ->exists();

        if (! $alreadyNotified) {
            $this->notificationService->notifyProjectFundingComplete($project);
        }
    }
}
