<?php

namespace App\Services;

use App\Enums\DonationStatus;
use App\Enums\ProjectStatus;
use App\Models\Donation;
use App\Models\Project;
use App\Models\User;
use App\UserRole;

class StatisticsService
{
    /**
     * Отримати загальну статистику платформи
     *
     * @return array{
     *     total_projects: int,
     *     active_projects: int,
     *     completed_projects: int,
     *     total_donations: float,
     *     total_donors: int,
     *     total_artists: int
     * }
     */
    public function getPlatformStatistics(): array
    {
        $totalProjects = Project::count();

        $activeProjects = Project::whereIn('status', [
            ProjectStatus::Announced,
            ProjectStatus::InProgress,
        ])->count();

        $completedProjects = Project::where('status', ProjectStatus::Completed)->count();

        $totalDonations = Donation::where('status', DonationStatus::Paid)
            ->sum('amount_usd');

        $totalDonors = Donation::where('status', DonationStatus::Paid)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $totalArtists = User::whereIn('role', [
            UserRole::Owner,
            UserRole::User,
        ])->whereHas('projects')->count();

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'total_donations' => round($totalDonations, 2),
            'total_donors' => $totalDonors,
            'total_artists' => $totalArtists,
        ];
    }

    /**
     * Отримати статистику проєкту
     *
     * @return array{
     *     budget_goal: float,
     *     budget_collected: float,
     *     progress_percentage: float,
     *     donors_count: int,
     *     likes_count: int,
     *     bonuses_claimed: int,
     *     days_remaining: int|null
     * }
     */
    public function getProjectStatistics(Project $project): array
    {
        $progress = $project->budget_goal > 0
            ? round(($project->budget_collected / $project->budget_goal) * 100, 2)
            : 0;

        $daysRemaining = null;
        if ($project->planned_completion_at && $project->planned_completion_at->isFuture()) {
            $daysRemaining = now()->diffInDays($project->planned_completion_at);
        }

        $bonusesClaimed = $project->bonuses()->sum('quantity_claimed');

        return [
            'budget_goal' => $project->budget_goal,
            'budget_collected' => $project->budget_collected,
            'progress_percentage' => min($progress, 100),
            'donors_count' => $project->donors_count,
            'likes_count' => $project->likes_count,
            'bonuses_claimed' => $bonusesClaimed,
            'days_remaining' => $daysRemaining,
        ];
    }

    /**
     * Отримати статистику донатів за період
     *
     * @return array{
     *     total_amount: float,
     *     donations_count: int,
     *     average_donation: float,
     *     by_currency: array<string, float>,
     *     by_day: array<string, float>
     * }
     */
    public function getDonationsStatistics(
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null
    ): array {
        $query = Donation::where('status', DonationStatus::Paid);

        if ($startDate) {
            $query->where('paid_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('paid_at', '<=', $endDate);
        }

        $donations = $query->get();

        $totalAmount = $donations->sum('amount_usd');
        $donationsCount = $donations->count();
        $averageDonation = $donationsCount > 0 ? $totalAmount / $donationsCount : 0;

        // По валютам
        $byCurrency = $donations->groupBy('currency')
            ->map(fn ($group) => round($group->sum('amount'), 2))
            ->toArray();

        // По дням
        $byDay = $donations->groupBy(fn ($d) => $d->paid_at->format('Y-m-d'))
            ->map(fn ($group) => round($group->sum('amount_usd'), 2))
            ->toArray();

        return [
            'total_amount' => round($totalAmount, 2),
            'donations_count' => $donationsCount,
            'average_donation' => round($averageDonation, 2),
            'by_currency' => $byCurrency,
            'by_day' => $byDay,
        ];
    }

    /**
     * Отримати топ проєктів за зборами
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Project>
     */
    public function getTopProjectsByFunding(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Project::whereIn('status', ProjectStatus::publicStatuses())
            ->orderByDesc('budget_collected')
            ->limit($limit)
            ->with('user')
            ->get();
    }

    /**
     * Отримати топ донаторів
     *
     * @return \Illuminate\Support\Collection<int, array{user: User, total_donated: float, donations_count: int}>
     */
    public function getTopDonors(int $limit = 10): \Illuminate\Support\Collection
    {
        return Donation::query()
            ->where('status', DonationStatus::Paid)
            ->whereNotNull('user_id')
            ->select('user_id')
            ->selectRaw('SUM(amount_usd) as total_donated')
            ->selectRaw('COUNT(*) as donations_count')
            ->groupBy('user_id')
            ->orderByDesc('total_donated')
            ->limit($limit)
            ->with('user')
            ->get()
            ->map(fn ($row) => [
                'user' => $row->user,
                'total_donated' => round($row->total_donated, 2),
                'donations_count' => $row->donations_count,
            ]);
    }

    /**
     * Отримати статистику користувача
     *
     * @return array{
     *     projects_count: int,
     *     completed_projects: int,
     *     total_raised: float,
     *     total_donated: float,
     *     donations_received: int
     * }
     */
    public function getUserStatistics(User $user): array
    {
        $projectsCount = $user->projects()->count();
        $completedProjects = $user->projects()->where('status', ProjectStatus::Completed)->count();

        $totalRaised = $user->projects()
            ->withSum(['donations' => fn ($q) => $q->where('status', DonationStatus::Paid)], 'amount_usd')
            ->get()
            ->sum('donations_sum_amount_usd');

        $totalDonated = Donation::where('user_id', $user->id)
            ->where('status', DonationStatus::Paid)
            ->sum('amount_usd');

        $donationsReceived = Donation::whereHas('project', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', DonationStatus::Paid)
            ->count();

        return [
            'projects_count' => $projectsCount,
            'completed_projects' => $completedProjects,
            'total_raised' => round($totalRaised ?? 0, 2),
            'total_donated' => round($totalDonated, 2),
            'donations_received' => $donationsReceived,
        ];
    }

    /**
     * Отримати статистику по категоріях мистецтва
     *
     * @return array<string, array{projects_count: int, total_raised: float}>
     */
    public function getCategoryStatistics(): array
    {
        return Project::query()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->select('art_category')
            ->selectRaw('COUNT(*) as projects_count')
            ->selectRaw('SUM(budget_collected) as total_raised')
            ->groupBy('art_category')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->art_category->value => [
                    'label' => $row->art_category->getLabel(),
                    'projects_count' => $row->projects_count,
                    'total_raised' => round($row->total_raised ?? 0, 2),
                ],
            ])
            ->toArray();
    }
}
