<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ArtCategory;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StatisticsController extends Controller
{
    /**
     * Отримати загальну статистику платформи
     */
    public function index(): JsonResponse
    {
        $stats = Cache::remember('platform_statistics', 300, function () {
            return $this->calculatePlatformStatistics();
        });

        return response()->json([
            'result' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Отримати статистику по проєктах
     */
    public function projects(Request $request): JsonResponse
    {
        $period = $request->input('period', 'all'); // all, year, month

        $cacheKey = "project_statistics_{$period}";
        $stats = Cache::remember($cacheKey, 300, function () use ($period) {
            return $this->calculateProjectStatistics($period);
        });

        return response()->json([
            'result' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Отримати статистику по донатах
     */
    public function donations(Request $request): JsonResponse
    {
        $period = $request->input('period', 'year'); // year, month, week

        $cacheKey = "donation_statistics_{$period}";
        $stats = Cache::remember($cacheKey, 300, function () use ($period) {
            return $this->calculateDonationStatistics($period);
        });

        return response()->json([
            'result' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Розрахувати загальну статистику платформи
     *
     * @return array<string, mixed>
     */
    private function calculatePlatformStatistics(): array
    {
        $publicStatuses = ProjectStatus::publicStatuses();

        // Загальна статистика
        $totalCollected = Donation::where('status', 'completed')->sum('amount');
        $totalProjects = Project::whereIn('status', $publicStatuses)->count();
        $activeProjects = Project::whereIn('status', [ProjectStatus::Announced, ProjectStatus::InProgress])->count();
        $completedProjects = Project::where('status', ProjectStatus::Completed)->count();
        $soldProjects = Project::where('status', ProjectStatus::Sold)->count();
        $totalSupporters = Donation::where('status', 'completed')->distinct('user_id')->count('user_id');
        $totalArtists = User::whereHas('projects', fn ($q) => $q->whereIn('status', $publicStatuses))->count();

        // Місячна статистика за останній рік
        $monthly = $this->getMonthlyStatistics();

        // Статистика по категоріях мистецтва
        $byArtForm = $this->getStatisticsByArtCategory();

        return [
            'platform' => [
                'total_collected' => (float) $totalCollected,
                'total_projects' => $totalProjects,
                'active_projects' => $activeProjects,
                'completed_projects' => $completedProjects,
                'sold_projects' => $soldProjects,
                'total_supporters' => $totalSupporters,
                'total_artists' => $totalArtists,
            ],
            'monthly' => $monthly,
            'by_art_form' => $byArtForm,
        ];
    }

    /**
     * Розрахувати статистику по проєктах
     *
     * @return array<string, mixed>
     */
    private function calculateProjectStatistics(string $period): array
    {
        $query = Project::query();

        // Застосовуємо фільтр по періоду
        if ($period === 'year') {
            $query->where('created_at', '>=', now()->subYear());
        } elseif ($period === 'month') {
            $query->where('created_at', '>=', now()->subMonth());
        }

        // Загальна кількість по статусах
        $byStatus = [];
        foreach (ProjectStatus::cases() as $status) {
            $count = (clone $query)->where('status', $status->value)->count();
            $byStatus[$status->value] = [
                'count' => $count,
                'label' => [
                    'uk' => $status->getLabel(),
                    'en' => $status->value,
                ],
            ];
        }

        // По категоріях мистецтва
        $byCategory = [];
        foreach (ArtCategory::cases() as $category) {
            $categoryQuery = (clone $query)->where('art_category', $category->value);
            $count = $categoryQuery->count();
            $collected = (float) $categoryQuery->sum('budget_collected');
            $goal = (float) $categoryQuery->sum('budget_goal');

            $byCategory[$category->value] = [
                'count' => $count,
                'collected' => $collected,
                'goal' => $goal,
                'label' => [
                    'uk' => $category->getLabel(),
                    'en' => $category->value,
                ],
            ];
        }

        // Топ проєктів по зборам
        $topProjects = Project::query()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->orderBy('budget_collected', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'slug' => $project->slug,
                'title' => $project->title,
                'collected' => (float) $project->budget_collected,
                'goal' => (float) $project->budget_goal,
                'donors_count' => $project->donors_count,
            ]);

        return [
            'period' => $period,
            'by_status' => $byStatus,
            'by_category' => $byCategory,
            'top_projects' => $topProjects,
        ];
    }

    /**
     * Розрахувати статистику по донатах
     *
     * @return array<string, mixed>
     */
    private function calculateDonationStatistics(string $period): array
    {
        $startDate = match ($period) {
            'year' => now()->subYear(),
            'month' => now()->subMonth(),
            'week' => now()->subWeek(),
            default => now()->subYear(),
        };

        // Загальна сума
        $total = Donation::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        // Кількість донатів
        $count = Donation::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->count();

        // Середня сума
        $average = $count > 0 ? $total / $count : 0;

        // По днях/місяцях - кросс-платформний запит
        $timeline = $this->getDonationTimeline($startDate, $period);

        // Розподіл по сумах
        $distribution = [
            'small' => Donation::where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->where('amount', '<', 500)
                ->count(),
            'medium' => Donation::where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->whereBetween('amount', [500, 2000])
                ->count(),
            'large' => Donation::where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->where('amount', '>', 2000)
                ->count(),
        ];

        return [
            'period' => $period,
            'summary' => [
                'total' => (float) $total,
                'count' => $count,
                'average' => round($average, 2),
            ],
            'timeline' => $timeline,
            'distribution' => $distribution,
        ];
    }

    /**
     * Отримати timeline донатів (кросс-платформний спосіб)
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDonationTimeline(\Carbon\Carbon $startDate, string $period): array
    {
        $donations = Donation::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->get(['created_at', 'amount']);

        $format = $period === 'week' ? 'Y-m-d' : 'Y-m';
        $grouped = $donations->groupBy(fn ($d) => $d->created_at->format($format));

        return $grouped->map(fn ($items, $periodKey) => [
            'period' => $periodKey,
            'total' => (float) $items->sum('amount'),
            'count' => $items->count(),
        ])->sortKeys()->values()->toArray();
    }

    /**
     * Отримати місячну статистику за останній рік
     *
     * @return array<int, array<string, mixed>>
     */
    private function getMonthlyStatistics(): array
    {
        $startDate = now()->subYear()->startOfMonth();

        $donations = Donation::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->get(['created_at', 'amount', 'project_id', 'user_id']);

        $grouped = $donations->groupBy(fn ($d) => $d->created_at->format('Y-m'));

        return $grouped->map(fn ($items, $month) => [
            'month' => $month,
            'collected' => (float) $items->sum('amount'),
            'projects' => $items->pluck('project_id')->unique()->count(),
            'supporters' => $items->pluck('user_id')->unique()->filter()->count(),
        ])->sortKeys()->values()->toArray();
    }

    /**
     * Отримати статистику по категоріях мистецтва
     *
     * @return array<int, array<string, mixed>>
     */
    private function getStatisticsByArtCategory(): array
    {
        $result = [];

        foreach (ArtCategory::cases() as $category) {
            $projects = Project::where('art_category', $category->value)
                ->whereIn('status', ProjectStatus::publicStatuses());

            $count = $projects->count();
            $collected = (float) (clone $projects)->sum('budget_collected');

            if ($count > 0) {
                $result[] = [
                    'art_form' => [
                        'uk' => $category->getLabel(),
                        'en' => $category->value,
                    ],
                    'count' => $count,
                    'collected' => $collected,
                ];
            }
        }

        return $result;
    }
}
