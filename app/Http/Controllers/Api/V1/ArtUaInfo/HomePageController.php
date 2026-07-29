<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\HomePage;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * Копія App\Http\Controllers\Api\HomePageController::index() (save-art) —
 * відмінність: forArtUaInfo() замість forSaveArt() у рекомендованих проєктах
 * та статистиці. statistics/chart-піделнпоінти art-ua-info не використовує,
 * тож не дублюються.
 *
 * @OA\Tag(
 *     name="HomePage (Art-UA Info)",
 *     description="API для головної сторінки art-ua-info"
 * )
 */
class HomePageController extends Controller
{
    /**
     * Отримати дані головної сторінки
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/home",
     *     operationId="artUaInfoGetHomePage",
     *     tags={"HomePage (Art-UA Info)"},
     *     summary="Дані головної сторінки",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="language", in="query", description="Мова контенту (uk, en)", @OA\Schema(type="string", enum={"uk", "en"}, default="uk")),
     *
     *     @OA\Response(response=200, description="Дані головної сторінки"),
     *     @OA\Response(response=404, description="Головна сторінка не налаштована")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $language = $request->get('language', 'uk');

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

        $featuredProjects = Project::query()
            ->forArtUaInfo()
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
                'art_category' => $project->getArtCategorySlug(),
                'art_category_label' => $project->getArtCategoryLabel('uk'),
                'likes_count' => $project->likes_count,
                'author' => [
                    'id' => $project->user->id,
                    'name' => $project->user->name,
                    'slug' => $project->user->slug,
                ],
            ]);

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

    private function getStatisticsData(): array
    {
        $homePage = HomePage::getActive();
        $isActive = $homePage?->statistics_is_active ?? true;

        if ($isActive && $homePage) {
            return [
                'is_active' => $isActive,
                'total_collected' => (float) ($homePage->total_collected ?? 0),
                'declared_projects' => $homePage->declared_projects ?? 0,
                'active_projects' => $homePage->active_projects ?? 0,
                'completed_projects' => $homePage->completed_projects ?? 0,
                'sold_projects' => $homePage->sold_projects ?? 0,
            ];
        }

        $totalCollected = Project::forArtUaInfo()->whereIn('status', ProjectStatus::publicStatuses())
            ->sum('budget_collected');

        return [
            'is_active' => $isActive,
            'total_collected' => (float) $totalCollected,
            'declared_projects' => Project::forArtUaInfo()->where('status', ProjectStatus::Announced)->count(),
            'active_projects' => Project::forArtUaInfo()->where('status', ProjectStatus::InProgress)->count(),
            'completed_projects' => Project::forArtUaInfo()->where('status', ProjectStatus::Completed)->count(),
            'sold_projects' => Project::forArtUaInfo()->where('status', ProjectStatus::Sold)->count(),
        ];
    }

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
