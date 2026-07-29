<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\NewsCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NewsListResource;
use App\Http\Resources\Api\V1\NewsResource;
use App\Models\News;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="News",
 *     description="API для новин та подій (art-ua-info)"
 * )
 */
class NewsController extends Controller
{
    /**
     * Отримати список новин та подій
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/news",
     *     operationId="getNews",
     *     tags={"News"},
     *     summary="Список новин та подій",
     *     description="Повертає пагінований список активних новин та подій, відсортований за датою публікації (спочатку найновіші). Підтримує фільтрацію за категорією.",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         required=false,
     *         description="Фільтр за категорією",
     *
     *         @OA\Schema(type="string", enum={"news", "event"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="language",
     *         in="query",
     *         required=false,
     *         description="Код мови (uk або en). Якщо не вказано - повертає всі мовні версії.",
     *
     *         @OA\Schema(type="string", enum={"uk", "en"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Кількість новин на сторінці (максимум 50)",
     *
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список новин та подій",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/NewsList")),
     *             @OA\Property(property="language", type="string", nullable=true, example="uk")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $language = $request->query('language');
        $supportedLanguages = ['uk', 'en'];
        if ($language && ! in_array($language, $supportedLanguages)) {
            $language = 'uk';
        }

        $query = News::query()->active();

        if ($request->filled('category')) {
            $category = NewsCategory::tryFrom($request->query('category'));
            if ($category) {
                $query->ofCategory($category);
            }
        }

        $query->orderByDesc('published_at');

        $perPage = min(max(1, (int) $request->input('per_page', 15)), 50);
        $news = $query->paginate($perPage);

        $collection = NewsListResource::collection($news);

        if ($language) {
            $collection->additional(['language' => $language]);
        }

        return $collection;
    }

    /**
     * Отримати новину/подію за slug
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/news/{slug}",
     *     operationId="getNewsItem",
     *     tags={"News"},
     *     summary="Новина/подія за slug",
     *     description="Повертає повну інформацію про новину або подію, включно з текстовими блоками.",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Slug новини", @OA\Schema(type="string")),
     *     @OA\Parameter(
     *         name="language",
     *         in="query",
     *         required=false,
     *         description="Код мови (uk або en). Якщо не вказано - повертає всі мовні версії.",
     *
     *         @OA\Schema(type="string", enum={"uk", "en"})
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Новина/подія",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/News"),
     *             @OA\Property(property="language", type="string", nullable=true, example="uk")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Новину не знайдено")
     * )
     */
    public function show(Request $request, string $slug)
    {
        $news = News::query()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        $resource = new NewsResource($news);

        $language = $request->query('language');
        $supportedLanguages = ['uk', 'en'];
        if ($language && in_array($language, $supportedLanguages)) {
            return $resource->additional(['language' => $language]);
        }

        return $resource;
    }
}
