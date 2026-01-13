<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\HomePage;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
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
     *                 @OA\Property(property="footer_expert", type="object"),
     *                 @OA\Property(property="featured_projects", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Головна сторінка не налаштована")
     * )
     */
    public function index(): JsonResponse
    {
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
                'title' => $project->title,
                'short_description' => $project->short_description,
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
                'statistics' => [
                    'total_collected' => $homePage->total_collected ?? 0,
                    'declared_projects' => $homePage->declared_projects ?? 0,
                    'active_projects' => $homePage->active_projects ?? 0,
                    'completed_projects' => $homePage->completed_projects ?? 0,
                    'sold_projects' => $homePage->sold_projects ?? 0,
                ],
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
                'footer_expert' => [
                    'title' => $homePage->footer_expert_title,
                    'text' => $homePage->footer_expert_text,
                    'features' => $homePage->footer_expert_features,
                    'button_text' => $homePage->footer_expert_button_text,
                ],
                'featured_projects' => $featuredProjects,
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
        // Обчислюємо статистику з бази даних
        $totalCollected = Project::whereIn('status', ProjectStatus::publicStatuses())
            ->sum('budget_collected');

        $declaredProjects = Project::where('status', ProjectStatus::Announced)->count();
        $activeProjects = Project::where('status', ProjectStatus::InProgress)->count();
        $completedProjects = Project::where('status', ProjectStatus::Completed)->count();
        $soldProjects = Project::where('status', ProjectStatus::Sold)->count();

        return response()->json([
            'data' => [
                'total_collected' => (float) $totalCollected,
                'declared_projects' => $declaredProjects,
                'active_projects' => $activeProjects,
                'completed_projects' => $completedProjects,
                'sold_projects' => $soldProjects,
            ],
        ]);
    }
}
