<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\HomePage;
use App\Models\Project;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="HomePage",
 *     description="API для головної сторінки"
 * )
 */
class HomePageController extends Controller
{
    /**
     * Отримати дані головної сторінки
     *
     * @OA\Get(
     *     path="/home",
     *     operationId="getHomePage",
     *     tags={"HomePage"},
     *     summary="Дані головної сторінки",
     *     description="Повертає всі дані для рендерингу головної сторінки, включаючи hero-секцію, статистику, партнерів та рекомендовані проекти",
     *
     *     @OA\Parameter(
     *         name="language",
     *         in="query",
     *         description="Мова контенту (uk, en)",
     *
     *         @OA\Schema(type="string", enum={"uk", "en"}, default="uk")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Дані головної сторінки",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="hero", type="object",
     *                     @OA\Property(property="title", type="object"),
     *                     @OA\Property(property="video_poster", type="string"),
     *                     @OA\Property(property="video_poster_mobile", type="string"),
     *                     @OA\Property(property="image_poster", type="string"),
     *                     @OA\Property(property="image_poster_mobile", type="string")
     *                 ),
     *                 @OA\Property(property="donates_section", type="object",
     *                     @OA\Property(property="subtitle", type="object"),
     *                     @OA\Property(property="title", type="object"),
     *                     @OA\Property(property="text", type="object")
     *                 ),
     *                 @OA\Property(property="statistics", type="object",
     *                     @OA\Property(property="total_collected", type="integer"),
     *                     @OA\Property(property="declared_projects", type="integer"),
     *                     @OA\Property(property="active_projects", type="integer"),
     *                     @OA\Property(property="completed_projects", type="integer"),
     *                     @OA\Property(property="sold_projects", type="integer")
     *                 ),
     *                 @OA\Property(property="partners", type="object",
     *                     @OA\Property(property="title", type="object"),
     *                     @OA\Property(property="items", type="array", @OA\Items(type="object"))
     *                 ),
     *                 @OA\Property(property="ad_blocks", type="object",
     *                     @OA\Property(property="first", type="object"),
     *                     @OA\Property(property="second", type="object")
     *                 ),
     *                 @OA\Property(property="featured_projects", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Головна сторінка не налаштована")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $language = $request->get('language', 'uk');

        // Валідація мови
        if (! in_array($language, ['uk', 'en'])) {
            $language = 'uk';
        }

        app()->setLocale($language);

        $homePage = HomePage::getActive();

        if (! $homePage) {
            return response()->json([
                'message' => 'Головна сторінка не налаштована',
            ], 404);
        }

        // Отримуємо рекомендовані проекти (останні 6 оголошених)
        $featuredProjects = Project::query()
            ->with('user')
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->orderBy('announced_at', 'desc')
            ->limit(6)
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'slug' => $project->slug,
                'title' => $this->extractTranslation($project->title, $language),
                'short_description' => $this->extractTranslation($project->short_description, $language),
                'cover_url' => $project->cover ? asset('storage/'.$project->cover) : null,
                'status' => $project->status->value,
                'status_label' => $project->status->getLabel(),
                'art_category' => $project->art_category?->value,
                'art_category_label' => $project->art_category?->getLabel(),
                'currency' => $project->currency->value,
                'budget_goal' => (float) $project->budget_goal,
                'budget_collected' => (float) $project->budget_collected,
                'progress_percentage' => $project->budget_goal > 0
                    ? round(($project->budget_collected / $project->budget_goal) * 100, 2)
                    : 0,
                'likes_count' => $project->likes_count,
                'donors_count' => $project->donors_count,
                'author' => [
                    'id' => $project->user->id,
                    'name' => $project->user->name,
                    'slug' => $project->user->slug,
                ],
            ]);

        // Форматуємо партнерів
        $partners = collect($homePage->partners ?? [])->map(function ($partner, $index) use ($homePage) {
            return [
                'logo' => $homePage->getPartnerLogo($index),
                'name' => $partner['name'] ?? null,
                'description' => $partner['description'] ?? null,
                'url' => $partner['url'] ?? null,
            ];
        });

        return response()->json([
            'data' => [
                'hero' => [
                    'title' => $homePage->hero_title,
                    'video_poster' => $homePage->hero_video_poster
                        ? asset('storage/'.$homePage->hero_video_poster)
                        : null,
                    'video_poster_mobile' => $homePage->hero_video_poster_m
                        ? asset('storage/'.$homePage->hero_video_poster_m)
                        : null,
                    'image_poster' => $homePage->hero_image_poster
                        ? asset('storage/'.$homePage->hero_image_poster)
                        : null,
                    'image_poster_mobile' => $homePage->hero_image_poster_m
                        ? asset('storage/'.$homePage->hero_image_poster_m)
                        : null,
                ],
                'donates_section' => [
                    'subtitle' => $homePage->donates_subtitle,
                    'title' => $homePage->donates_title,
                    'text' => $homePage->donates_text,
                ],
                'featured_projects' => $featuredProjects,
                'statistics' => $this->getStatisticsData(),
                'partners' => [
                    'title' => $homePage->partners_title,
                    'items' => $partners,
                ],
                'ad_blocks' => [
                    'first' => [
                        'title' => $homePage->ad_first_title,
                        'button_text' => $homePage->ad_first_button_text,
                        'image' => $homePage->ad_first_image
                            ? asset('storage/'.$homePage->ad_first_image)
                            : null,
                    ],
                    'second' => [
                        'title' => $homePage->ad_second_title,
                        'button_text' => $homePage->ad_second_button_text,
                        'image' => $homePage->ad_second_image
                            ? asset('storage/'.$homePage->ad_second_image)
                            : null,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Отримати статистику платформи в реальному часі
     *
     * @OA\Get(
     *     path="/home/statistics",
     *     operationId="getHomeStatistics",
     *     tags={"HomePage"},
     *     summary="Статистика платформи",
     *     description="Повертає актуальну статистику платформи (загальна сума зборів, кількість проектів)",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Статистика",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_collected", type="number", example=2325250),
     *                 @OA\Property(property="declared_projects", type="integer", example=624),
     *                 @OA\Property(property="active_projects", type="integer", example=387),
     *                 @OA\Property(property="completed_projects", type="integer", example=1126),
     *                 @OA\Property(property="sold_projects", type="integer", example=107)
     *             )
     *         )
     *     )
     * )
     */
    public function statistics(): JsonResponse
    {
        return response()->json([
            'data' => $this->getStatisticsData(),
        ]);
    }

    /**
     * Отримати дані статистики з бази даних
     *
     * @return array<string, int|float>
     */
    private function getStatisticsData(): array
    {
        $totalCollected = Project::whereIn('status', ProjectStatus::publicStatuses())
            ->sum('budget_collected');

        return [
            'total_collected' => (float) $totalCollected,
            'declared_projects' => Project::where('status', ProjectStatus::Announced)->count(),
            'active_projects' => Project::where('status', ProjectStatus::InProgress)->count(),
            'completed_projects' => Project::where('status', ProjectStatus::Completed)->count(),
            'sold_projects' => Project::where('status', ProjectStatus::Sold)->count(),
        ];
    }

    /**
     * Отримати дані для графіка зборів
     *
     * @OA\Get(
     *     path="/home/chart",
     *     operationId="getHomeChart",
     *     tags={"HomePage"},
     *     summary="Дані для графіка зборів",
     *     description="Повертає дані для графіка зборів за обраний період (день, тиждень, місяць, рік, все)",
     *
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Період для графіка: day (день), week (тиждень), month (місяць), year (рік), all (все)",
     *
     *         @OA\Schema(type="string", enum={"day", "week", "month", "year", "all"}, default="month")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Дані для графіка",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="period", type="string", example="month"),
     *                 @OA\Property(property="total", type="number", example=2325250),
     *                 @OA\Property(property="labels", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="values", type="array", @OA\Items(type="number"))
     *             )
     *         )
     *     )
     * )
     */
    public function chart(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');

        $data = match ($period) {
            'day' => $this->getChartDataByHours(),
            'week' => $this->getChartDataByDays(7),
            'month' => $this->getChartDataByDays(31),
            'year' => $this->getChartDataByMonths(12),
            'all' => $this->getChartDataAllTime(),
            default => $this->getChartDataByDays(31),
        };

        return response()->json([
            'data' => [
                'period' => $period,
                'total' => $data['total'],
                'labels' => $data['labels'],
                'values' => $data['values'],
            ],
        ]);
    }

    /**
     * Дані графіка по годинах (за сьогодні)
     *
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    private function getChartDataByHours(): array
    {
        $today = Carbon::today();
        $labels = [];
        $values = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $labels[] = sprintf('%02d:00', $hour);

            $amount = Donation::where('status', 'completed')
                ->whereDate('paid_at', $today)
                ->whereRaw('HOUR(paid_at) = ?', [$hour])
                ->sum('amount');

            $values[] = (float) $amount;
        }

        return [
            'total' => array_sum($values),
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Дані графіка по днях
     *
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    private function getChartDataByDays(int $days): array
    {
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $period = CarbonPeriod::create($startDate, $endDate);

        $labels = [];
        $values = [];

        $donations = Donation::where('status', 'completed')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        foreach ($period as $date) {
            $dateKey = $date->format('Y-m-d');
            $labels[] = $date->format('j'); // день місяця (1-31)
            $values[] = (float) ($donations[$dateKey] ?? 0);
        }

        return [
            'total' => array_sum($values),
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Дані графіка по місяцях
     *
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    private function getChartDataByMonths(int $months): array
    {
        $labels = [];
        $values = [];

        $donations = Donation::where('status', 'completed')
            ->where('paid_at', '>=', Carbon::now()->subMonths($months)->startOfMonth())
            ->selectRaw('YEAR(paid_at) as year, MONTH(paid_at) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn ($item) => $item->year.'-'.str_pad($item->month, 2, '0', STR_PAD_LEFT))
            ->map(fn ($item) => (float) $item->total)
            ->toArray();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $labels[] = $date->translatedFormat('M'); // Скорочена назва місяця
            $values[] = $donations[$key] ?? 0;
        }

        return [
            'total' => array_sum($values),
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Дані графіка за весь час (по роках)
     *
     * @return array{total: float, labels: array<string>, values: array<float>}
     */
    private function getChartDataAllTime(): array
    {
        $donations = Donation::where('status', 'completed')
            ->selectRaw('YEAR(paid_at) as year, SUM(amount) as total')
            ->groupBy('year')
            ->orderBy('year')
            ->pluck('total', 'year')
            ->toArray();

        $labels = array_map('strval', array_keys($donations));
        $values = array_map('floatval', array_values($donations));

        return [
            'total' => array_sum($values),
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Витягти переклад з мультимовного поля
     *
     * @param  array|string|null  $value
     */
    private function extractTranslation($value, string $language): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            return $value[$language] ?? $value['uk'] ?? null;
        }

        return null;
    }
}
