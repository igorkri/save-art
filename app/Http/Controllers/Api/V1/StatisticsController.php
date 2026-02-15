<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\ArtCategory;
use App\Models\Donation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Statistics",
 *     description="API для отримання статистики платформи"
 * )
 */
class StatisticsController extends Controller
{
    /**
     * Отримати загальну статистику платформи
     *
     * @OA\Get(
     *     path="/v1/statistics",
     *     operationId="getPlatformStatistics",
     *     tags={"Statistics"},
     *     summary="Загальна статистика",
     *     description="Повертає загальну статистику платформи: збори, проєкти, митці, меценати",
     *
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Статистика платформи",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="platform", type="object",
     *                     @OA\Property(property="total_collected", type="number", format="float", example=1500000.00, description="Загальна сума зборів (UAH)"),
     *                     @OA\Property(property="total_projects", type="integer", example=150, description="Всього проєктів"),
     *                     @OA\Property(property="active_projects", type="integer", example=45, description="Активних проєктів"),
     *                     @OA\Property(property="completed_projects", type="integer", example=80, description="Завершених проєктів"),
     *                     @OA\Property(property="sold_projects", type="integer", example=25, description="Проданих проєктів"),
     *                     @OA\Property(property="total_supporters", type="integer", example=3500, description="Унікальних меценатів"),
     *                     @OA\Property(property="total_artists", type="integer", example=120, description="Митців з проєктами")
     *                 ),
     *                 @OA\Property(property="monthly", type="array", description="Помісячна статистика за рік",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="month", type="string", example="2025-01"),
     *                         @OA\Property(property="collected", type="number", example=125000),
     *                         @OA\Property(property="donors", type="integer", example=85),
     *                         @OA\Property(property="projects_created", type="integer", example=12)
     *                     )
     *                 ),
     *                 @OA\Property(property="by_art_form", type="array", description="Статистика по категоріях",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="category", type="string", example="visual"),
     *                         @OA\Property(property="label", type="string", example="Візуальне мистецтво"),
     *                         @OA\Property(property="projects_count", type="integer", example=45),
     *                         @OA\Property(property="total_collected", type="number", example=450000)
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $language = $this->getLanguage($request);

        $stats = Cache::remember('platform_statistics', 300, function () {
            return $this->calculatePlatformStatistics();
        });

        // Локалізуємо by_art_form після кешу
        if ($language && isset($stats['by_art_form'])) {
            $stats['by_art_form'] = array_map(function ($item) use ($language) {
                $item['art_form'] = $this->localizeValue($item['art_form'], $language);

                return $item;
            }, $stats['by_art_form']);
        }

        return response()->json([
            'result' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Отримати статистику по проєктах
     *
     * @OA\Get(
     *     path="/v1/statistics/projects",
     *     operationId="getProjectStatistics",
     *     tags={"Statistics"},
     *     summary="Статистика проєктів",
     *     description="Повертає детальну статистику по проєктах за період",
     *
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Період статистики",
     *
     *         @OA\Schema(type="string", enum={"all", "year", "month"}, default="all"),
     *         example="year"
     *     ),
     *
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Статистика проєктів",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function projects(Request $request): JsonResponse
    {
        $period = $request->input('period', 'all'); // all, year, month
        $language = $this->getLanguage($request);

        $cacheKey = "project_statistics_{$period}";
        $stats = Cache::remember($cacheKey, 300, function () use ($period) {
            return $this->calculateProjectStatistics($period);
        });

        // Локалізуємо після кешу
        if ($language) {
            if (isset($stats['by_status'])) {
                foreach ($stats['by_status'] as $key => $item) {
                    $stats['by_status'][$key]['label'] = $this->localizeValue($item['label'], $language);
                }
            }
            if (isset($stats['by_category'])) {
                foreach ($stats['by_category'] as $key => $item) {
                    $stats['by_category'][$key]['label'] = $this->localizeValue($item['label'], $language);
                }
            }
            if (isset($stats['top_projects'])) {
                $stats['top_projects'] = $stats['top_projects']->map(function ($item) use ($language) {
                    $item['title'] = $this->localizeValue($item['title'], $language);

                    return $item;
                });
            }
        }

        return response()->json([
            'result' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Отримати статистику по донатах
     *
     * @OA\Get(
     *     path="/v1/statistics/donations",
     *     operationId="getDonationStatistics",
     *     tags={"Statistics"},
     *     summary="Статистика донатів",
     *     description="Повертає детальну статистику по донатах за період",
     *
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Період статистики",
     *
     *         @OA\Schema(type="string", enum={"year", "month", "week"}, default="year"),
     *         example="month"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Статистика донатів",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
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
        foreach (ArtCategory::whereNull('parent_id')->orderBy('sort_order')->get() as $category) {
            $categoryIds = [$category->id, ...$category->children()->pluck('id')];
            $categoryQuery = (clone $query)->whereIn('art_category_id', $categoryIds);
            $count = $categoryQuery->count();
            $collected = (float) $categoryQuery->sum('budget_collected');
            $goal = (float) $categoryQuery->sum('budget_goal');

            $byCategory[$category->slug] = [
                'count' => $count,
                'collected' => $collected,
                'goal' => $goal,
                'label' => [
                    'uk' => $category->getLabel('uk'),
                    'en' => $category->getLabel('en'),
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

        foreach (ArtCategory::with('children')->whereNull('parent_id')->orderBy('sort_order')->get() as $category) {
            $categoryIds = [$category->id, ...$category->children->pluck('id')];
            $projects = Project::whereIn('art_category_id', $categoryIds)
                ->whereIn('status', ProjectStatus::publicStatuses());

            $count = $projects->count();
            $collected = (float) (clone $projects)->sum('budget_collected');

            if ($count > 0) {
                $result[] = [
                    'art_form' => [
                        'uk' => $category->getLabel('uk'),
                        'en' => $category->getLabel('en'),
                    ],
                    'count' => $count,
                    'collected' => $collected,
                ];
            }
        }

        return $result;
    }

    /**
     * Отримати мову з запиту
     */
    private function getLanguage(Request $request): ?string
    {
        $language = $request->query('language');

        return ($language && in_array($language, ['uk', 'en'])) ? $language : null;
    }

    /**
     * Локалізувати значення поля
     */
    private function localizeValue(mixed $value, ?string $language): mixed
    {
        if ($language === null || ! is_array($value)) {
            return $value;
        }

        if (isset($value['uk']) || isset($value['en'])) {
            return $value[$language] ?? $value['uk'] ?? reset($value);
        }

        return $value;
    }
}
