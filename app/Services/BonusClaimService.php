<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\ProjectBonus;
use Illuminate\Support\Facades\DB;

class BonusClaimService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Знайти підходящий бонус для суми доната
     */
    public function findMatchingBonus(int $projectId, float $amount): ?ProjectBonus
    {
        return ProjectBonus::query()
            ->where('project_id', $projectId)
            ->where('min_donation', '<=', $amount)
            ->where(function ($query) {
                $query->whereNull('quantity')
                    ->orWhereRaw('quantity_claimed < quantity');
            })
            ->orderByDesc('min_donation')
            ->first();
    }

    /**
     * Отримати всі доступні бонуси для проєкту
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ProjectBonus>
     */
    public function getAvailableBonuses(int $projectId): \Illuminate\Database\Eloquent\Collection
    {
        return ProjectBonus::query()
            ->where('project_id', $projectId)
            ->where(function ($query) {
                $query->whereNull('quantity')
                    ->orWhereRaw('quantity_claimed < quantity');
            })
            ->orderBy('min_donation')
            ->get();
    }

    /**
     * Перевірити, чи може донатер отримати бонус
     */
    public function canClaimBonus(ProjectBonus $bonus, float $donationAmount): bool
    {
        // Перевіряємо мінімальну суму
        if ($donationAmount < $bonus->min_donation) {
            return false;
        }

        // Перевіряємо доступність бонусу
        return $bonus->isAvailable();
    }

    /**
     * Призначити бонус до доната
     */
    public function claimBonus(Donation $donation, ProjectBonus $bonus): bool
    {
        if (! $this->canClaimBonus($bonus, $donation->amount)) {
            return false;
        }

        return DB::transaction(function () use ($donation, $bonus) {
            // Оновлюємо донат
            $donation->update([
                'project_bonus_id' => $bonus->id,
            ]);

            // Збільшуємо лічильник використаних бонусів
            $bonus->increment('quantity_claimed');

            // Відправляємо сповіщення донатеру
            if ($donation->user_id) {
                $this->notifyBonusClaimed($bonus, $donation);
            }

            return true;
        });
    }

    /**
     * Автоматично призначити бонус при оплаті доната
     */
    public function autoAssignBonus(Donation $donation): ?ProjectBonus
    {
        // Якщо бонус вже призначений, пропускаємо
        if ($donation->project_bonus_id) {
            return $donation->bonus;
        }

        // Знаходимо підходящий бонус
        $bonus = $this->findMatchingBonus($donation->project_id, $donation->amount);

        if ($bonus && $this->claimBonus($donation, $bonus)) {
            return $bonus;
        }

        return null;
    }

    /**
     * Скасувати бонус (наприклад, при рефанді)
     */
    public function revokeBonus(Donation $donation): bool
    {
        if (! $donation->project_bonus_id) {
            return false;
        }

        return DB::transaction(function () use ($donation) {
            $bonus = $donation->bonus;

            // Знімаємо бонус з доната
            $donation->update([
                'project_bonus_id' => null,
            ]);

            // Зменшуємо лічильник використаних бонусів
            if ($bonus && $bonus->quantity_claimed > 0) {
                $bonus->decrement('quantity_claimed');
            }

            return true;
        });
    }

    /**
     * Отримати статистику бонусів проєкту
     *
     * @return array{
     *     total_bonuses: int,
     *     total_claimed: int,
     *     total_available: int,
     *     bonuses: array<array{id: int, title: array, claimed: int, remaining: int|null}>
     * }
     */
    public function getBonusStatistics(int $projectId): array
    {
        $bonuses = ProjectBonus::query()
            ->where('project_id', $projectId)
            ->orderBy('order')
            ->get();

        $totalClaimed = 0;
        $totalAvailable = 0;
        $bonusDetails = [];

        foreach ($bonuses as $bonus) {
            $totalClaimed += $bonus->quantity_claimed;

            $remaining = $bonus->getRemaining();
            if ($remaining !== null) {
                $totalAvailable += $remaining;
            }

            $bonusDetails[] = [
                'id' => $bonus->id,
                'title' => $bonus->title,
                'min_donation' => $bonus->min_donation,
                'claimed' => $bonus->quantity_claimed,
                'remaining' => $remaining,
            ];
        }

        return [
            'total_bonuses' => $bonuses->count(),
            'total_claimed' => $totalClaimed,
            'total_available' => $totalAvailable,
            'bonuses' => $bonusDetails,
        ];
    }

    /**
     * Відправити сповіщення про отриманий бонус
     */
    private function notifyBonusClaimed(ProjectBonus $bonus, Donation $donation): void
    {
        $this->notificationService->notifyBonusClaimed($donation->user, $bonus, $donation);
    }
}
