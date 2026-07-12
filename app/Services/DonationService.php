<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DonationService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Врахувати донат у зібраній сумі проєкту одразу при створенні,
     * не чекаючи підтвердження реальної оплати (фронтенд поки не
     * має інтеграції з платіжною системою). Якщо пізніше підключиться
     * реальний webhook і processPaidDonation() почне викликатись для
     * цих же донатів — там потрібно буде прибрати повторний increment,
     * інакше сума порахується двічі.
     */
    public function registerPendingAsCollected(Donation $donation): void
    {
        if (! $donation->project_id) {
            return;
        }

        DB::transaction(function () use ($donation) {
            $project = $donation->project;
            $project->increment('budget_collected', $donation->amount);

            $existingDonor = Donation::where('project_id', $project->id)
                ->where('id', '!=', $donation->id)
                ->where(function ($q) use ($donation) {
                    if ($donation->user_id) {
                        $q->where('user_id', $donation->user_id);
                    } else {
                        $q->where('donor_email', $donation->donor_email);
                    }
                })
                ->exists();

            if (! $existingDonor) {
                $project->increment('donors_count');
            }
        });
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

            // Оновлюємо статистику проєкту
            $project = $donation->project;
            $project->increment('budget_collected', $donation->amount);

            // Збільшуємо кількість донатерів (унікальних)
            $existingDonor = Donation::where('project_id', $project->id)
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

            if (! $existingDonor) {
                $project->increment('donors_count');
            }

            // Якщо є бонус — резервуємо
            if ($donation->project_bonus_id && $donation->bonus) {
                $donation->bonus->increment('quantity_claimed');
            }

            Log::info('Donation processed successfully', [
                'donation_id' => $donation->id,
                'amount' => $donation->amount,
                'project_id' => $project->id,
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

            // Якщо донат був оплачений — відкатуємо статистику
            if ($oldStatus === 'paid') {
                $project = $donation->project;
                $project->decrement('budget_collected', $donation->amount);

                // Перевіряємо, чи є інші оплачені донати від цього донатера
                $hasOtherDonations = Donation::where('project_id', $project->id)
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

                if (! $hasOtherDonations) {
                    $project->decrement('donors_count');
                }

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
