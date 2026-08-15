<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Donation;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DonationService
{
    public function __construct(
        private ProjectWorkflowService $workflowService,
        private NotificationService $notificationService
    ) {}

    /**
     * TODO(payment-integration): демо-режим без платіжної системи.
     *
     * LiqPay ще не підключено (PaymentService::isConfigured() === false), тож
     * донат позначається оплаченим одразу при створенні, минаючи реальну
     * оплату — щоб можна було демонструвати збір коштів/бонуси/сповіщення
     * вже зараз. Викликається лише з DonationController::store() і
     * storePlatformDonation(), і лише коли платіж НЕ сконфігуровано — щойно
     * в .env з'являться робочі LIQPAY_PUBLIC_KEY/LIQPAY_PRIVATE_KEY, цей
     * виклик перестане спрацьовувати сам собою (гілка if в контролері), і
     * донати підуть по реальному шляху: 'pending' -> LiqPay -> webhook ->
     * processPaidDonation(). Коли платіжна система стане основним шляхом
     * для всіх користувачів — приберіть виклики markAsPaidForDemo() і саму
     * гілку "якщо не сконфігуровано" в контролері.
     */
    public function markAsPaidForDemo(Donation $donation): void
    {
        $this->processPaidDonation($donation);
    }

    /**
     * Обробити успішну оплату донату
     */
    public function processPaidDonation(Donation $donation): void
    {
        DB::transaction(function () use ($donation) {
            // Оновлюємо статус донату
            $donation->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Донат на платформу (без project_id) не впливає на статистику проєкту.
            // budget_collected/donors_count синхронізує DonationObserver
            // (реагує на перехід статусу в 'paid' незалежно від того, як
            // донат був створений/оновлений).
            $project = $donation->project;
            if ($project) {
                // Якщо є бонус — резервуємо
                if ($donation->project_bonus_id && $donation->bonus) {
                    $donation->bonus->increment('quantity_claimed');
                }

                $this->maybeStartWorkOnGoalReached($project);
            }

            Log::info('Donation processed successfully', [
                'donation_id' => $donation->id,
                'amount' => $donation->amount,
                'project_id' => $project?->id,
            ]);
        });
    }

    /**
     * Обробити невдалу оплату
     */
    public function processFailedDonation(Donation $donation): void
    {
        $donation->update([
            'status' => 'failed',
        ]);

        Log::warning('Donation payment failed', [
            'donation_id' => $donation->id,
        ]);
    }

    /**
     * Обробити рефанд
     */
    public function processRefund(Donation $donation): void
    {
        DB::transaction(function () use ($donation) {
            $oldStatus = $donation->status;

            $donation->update([
                'status' => 'refunded',
            ]);

            // budget_collected/donors_count відкочує DonationObserver
            // (реагує на перехід статусу з 'paid' в інший).
            if ($oldStatus === 'paid') {
                // Звільняємо бонус
                if ($donation->bonus_id && $donation->bonus) {
                    $donation->bonus->decrement('quantity_claimed');
                }
            }

            Log::info('Donation refunded', [
                'donation_id' => $donation->id,
            ]);
        });
    }

    /**
     * Перевірити, чи досягнуто ціль збору
     */
    public function checkGoalReached(Project $project): bool
    {
        return $project->budget_collected >= $project->budget_goal;
    }

    /**
     * Автоматично перевести проєкт з "оголошений" у "в роботі", щойно збір досяг мети
     * (docs/Flows.pdf: перехід ОГОЛОШЕНИЙ → В РОБОТІ відбувається системно, без дії митця).
     */
    private function maybeStartWorkOnGoalReached(Project $project): void
    {
        if ($project->status !== ProjectStatus::Announced) {
            return;
        }

        if (! $this->checkGoalReached($project)) {
            return;
        }

        if ($this->workflowService->startWork($project)) {
            $this->notificationService->notifyProjectFundingComplete($project);
        }
    }

    /**
     * Отримати відсоток зібраних коштів
     */
    public function getProgressPercentage(Project $project): float
    {
        if ($project->budget_goal <= 0) {
            return 0;
        }

        return min(100, round(($project->budget_collected / $project->budget_goal) * 100, 2));
    }
}
