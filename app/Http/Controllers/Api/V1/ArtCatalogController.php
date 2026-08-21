<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ArtCatalogResource;
use App\Models\ArtCatalog;
use App\Models\ArtCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Catalogs",
 *     description="PDF-каталоги робіт авторів"
 * )
 */
class ArtCatalogController extends Controller
{
    /**
     * Отримати список каталогів
     *
     * @OA\Get(
     *     path="/v1/catalogs",
     *     operationId="getArtCatalogs",
     *     tags={"Catalogs"},
     *     summary="Список PDF-каталогів",
     *     description="Повертає список каталогів робіт авторів з можливістю фільтрації за категорією мистецтва",
     *
     *     @OA\Parameter(name="art_category", in="query", description="Slug кореневої категорії мистецтва (можна кілька через кому)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="art_subcategory", in="query", description="Slug підкатегорії мистецтва (можна кілька через кому)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="author_slug", in="query", description="Slug автора (митця/організації) — обмежує список каталогами лише цього автора", @OA\Schema(type="string")),
     *     @OA\Parameter(name="search", in="query", description="Пошук по назві каталогу та імені автора", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort_by", in="query", @OA\Schema(type="string", enum={"date", "likes"})),
     *     @OA\Parameter(name="sort_dir", in="query", @OA\Schema(type="string", enum={"asc", "desc"})),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс. 50)", @OA\Schema(type="integer", default=15, maximum=50)),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список каталогів",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ArtCatalog")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->buildFilteredQuery($request)->with(['user', 'artCategory.parent']);

        if ($request->input('sort_by') === 'likes') {
            $query->orderBy('likes_count', $request->input('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('published_at', $request->input('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min($request->input('per_page', 15), 50);
        $catalogs = $query->paginate($perPage);

        $language = $request->query('language');

        return ArtCatalogResource::collection($catalogs)->additional([
            'filters' => [
                'categories' => $this->getFilterCategories($language, $request),
            ],
        ]);
    }

    /**
     * @param  string[]  $except
     */
    private function buildFilteredQuery(Request $request, array $except = []): Builder
    {
        $query = ArtCatalog::query();

        if (! in_array('art_subcategory', $except, true) && $request->filled('art_subcategory')) {
            $subSlugs = array_map('trim', explode(',', (string) $request->input('art_subcategory')));
            // Слаг може бути як підкатегорією, так і кореневою категорією без дітей (напр. "music") —
            // в обох випадках фільтруємо строго по її власному art_category_id.
            $subIds = ArtCategory::whereIn('slug', $subSlugs)->pluck('id')->all();
            $query->when(! empty($subIds), fn ($q) => $q->whereIn('art_category_id', $subIds));
        } elseif (! in_array('art_category', $except, true) && $request->filled('art_category')) {
            $slugs = array_map('trim', explode(',', (string) $request->input('art_category')));
            $categoryIds = ArtCategory::whereIn('slug', $slugs)
                ->get()
                ->flatMap(fn (ArtCategory $c) => $c->parent_id
                    ? [$c->id]
                    : [$c->id, ...$c->children()->pluck('id')]
                )
                ->unique()
                ->values()
                ->all();
            $query->when(! empty($categoryIds), fn ($q) => $q->whereIn('art_category_id', $categoryIds));
        }

        if (! in_array('author_slug', $except, true) && $request->filled('author_slug')) {
            $authorSlug = (string) $request->input('author_slug');
            $query->whereHas('user', fn ($q) => $q->where('slug', $authorSlug));
        }

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"]);
                    });
            });
        }

        return $query;
    }

    /**
     * Дерево категорій (корінь + підкатегорії) з кількістю каталогів для кожної —
     * враховує пошук, але не власний вибір підкатегорії (щоб чекбокси не зникали одна за одною).
     */
    private function getFilterCategories(?string $language, Request $request): array
    {
        $categories = [];
        $baseQuery = $this->buildFilteredQuery($request, ['art_subcategory', 'art_category']);

        foreach (ArtCategory::with('children')->whereNull('parent_id')->orderBy('sort_order')->get() as $root) {
            $subcategories = [];
            $categoryIds = [$root->id];

            foreach ($root->children as $child) {
                $categoryIds[] = $child->id;
                $count = (clone $baseQuery)->where('art_category_id', $child->id)->count();
                $subcategories[] = [
                    'slug' => $child->slug,
                    'name' => $child->name,
                    'catalogs_count' => $count,
                ];
            }

            $rootCount = (clone $baseQuery)->whereIn('art_category_id', $categoryIds)->count();

            $categories[] = [
                'slug' => $root->slug,
                'name' => $root->name,
                'catalogs_count' => $rootCount,
                'subcategories' => $subcategories,
            ];
        }

        return $categories;
    }

    private function getFilterTranslation(array $translations, ?string $language): string|array
    {
        if ($language === null) {
            return $translations;
        }

        return $translations[$language] ?? $translations['uk'] ?? reset($translations);
    }

    /**
     * Отримати каталог
     *
     * @OA\Get(
     *     path="/v1/catalogs/{id}",
     *     operationId="getArtCatalog",
     *     tags={"Catalogs"},
     *     summary="Каталог за ID",
     *     description="Повертає один PDF-каталог робіт автора",
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID каталогу", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Каталог",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/ArtCatalog")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Каталог не знайдено")
     * )
     */
    public function show(int $id): ArtCatalogResource
    {
        $catalog = ArtCatalog::query()
            ->with(['user', 'artCategory.parent'])
            ->findOrFail($id);

        return new ArtCatalogResource($catalog);
    }
}
