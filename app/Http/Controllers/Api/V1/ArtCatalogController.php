<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ArtCatalogResource;
use App\Models\ArtCatalog;
use App\Models\ArtCategory;
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
     *     @OA\Parameter(name="art_category", in="query", description="Slug кореневої категорії мистецтва", @OA\Schema(type="string")),
     *     @OA\Parameter(name="art_subcategory", in="query", description="Slug підкатегорії мистецтва", @OA\Schema(type="string")),
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
        $query = ArtCatalog::query()
            ->with(['user', 'artCategory'])
            ->orderByDesc('published_at');

        if ($request->filled('art_category') || $request->filled('art_subcategory')) {
            $categoryId = ArtCategory::resolveIdFromSlugs(
                $request->input('art_category'),
                $request->input('art_subcategory')
            );

            $query->when($categoryId, fn ($q) => $q->where('art_category_id', $categoryId));
        }

        $perPage = min($request->input('per_page', 15), 50);
        $catalogs = $query->paginate($perPage);

        return ArtCatalogResource::collection($catalogs);
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
            ->with(['user', 'artCategory'])
            ->findOrFail($id);

        return new ArtCatalogResource($catalog);
    }
}
