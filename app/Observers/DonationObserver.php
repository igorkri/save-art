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
     * Handle the Donation "created" event.
     *
     * Донат може бути одразу створений зі статусом 'paid' в обхід
     * DonationService (наприклад, вручну через адмінку Filament), тому
     * статистику проєкту синхронізуємо тут, а не лише в "updated".
     */
    public function created(Donation $donation): void
    {
        if ($donation->status === 'paid') {
            $this->syncProjectStats($donation, increment: true);
        }
    }

    /**
     * Handle the Donation "updated" event.
     * Надсилаємо сповіщення коли донат оплачено
     */
    public function updated(Donation $donation): void
    {
        if (! $donation->wasChanged('status')) {
            return;
        }

        $wasPaid = $donation->getOriginal('status') === 'paid';
        $isPaid = $donation->status === 'paid';

        if (! $wasPaid && $isPaid) {
            $this->syncProjectStats($donation, increment: true);

            // Сповіщення автору проєкту
            $this->notificationService->notifyDonationReceived($donation);

            // Сповіщення донатеру
            $this->notificationService->notifyDonationMade($donation);

            // Перевіряємо чи проект досяг мети
            $this->checkFundingComplete($donation);
        } elseif ($wasPaid && ! $isPaid) {
            $this->syncProjectStats($donation, increment: false);
        }
    }

    /**
     * Синхронізувати budget_collected та donors_count проєкту при переході
     * донату в статус 'paid' (increment: true) або з нього (increment: false).
     */
    private function syncProjectStats(Donation $donation, bool $increment): void
    {
        $project = $donation->project;

        if (! $project) {
            return;
        }

        if ($increment) {
            $project->increment('budget_collected', $donation->amount);
        } else {
            $project->decrement('budget_collected', $donation->amount);
        }

        // Чи є в проєкта інші оплачені донати від того ж донатера
        // (унікальність за user_id, або за donor_email для анонімних)
        $hasOtherPaidDonation = Donation::where('project_id', $project->id)
            ->where('id', '!=', $donation->id)
            ->where('status', 'paid')
            ->where(function ($q) use ($donation) {
                if ($donation->user_id) {
                    $q->where('user_id', $donation->user_id);
                } else {
                    $q->where('donor_email', $donation->donor_email);
                }
            })
            ->exists();

        if ($hasOtherPaidDonation) {
            return;
        }

        if ($increment) {
            $project->increment('donors_count');
        } else {
            $project->decrement('donors_count');
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

        // budget_collected — канонічний підсумок збору. Він уже оновлений
        // syncProjectStats() і також враховує імпортовані/початкові суми.
        $project->refresh();

        if ($project->budget_collected < $project->budget_goal) {
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
