<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Http\Controllers\Controller;
use App\Models\ArtCategory as ArtCategoryModel;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Catalog",
 *     description="Довідники та каталоги"
 * )
 */
class CatalogController extends Controller
{
    /**
     * Отримати список категорій мистецтва
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/categories",
     *     summary="Список категорій мистецтва",
     *     description="Повертає список всіх категорій мистецтва з перекладами та підкатегоріями",
     *     operationId="artUaInfoGetCategories",
     *     tags={"Catalog"},
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"}, default="uk")),
     *
     *     @OA\Response(response=200, description="Список категорій")
     * )
     */
    public function categories(): JsonResponse
    {
        $language = request('language', 'uk');

        $roots = ArtCategoryModel::with('children')->whereNull('parent_id')->orderBy('sort_order')->get();

        $list = $roots->map(fn (ArtCategoryModel $cat) => [
            'value' => $cat->slug,
            'label' => $cat->getLabel($language),
            'subcategories' => $cat->children->map(fn (ArtCategoryModel $sub) => [
                'value' => $sub->slug,
                'label' => $sub->getLabel($language),
            ])->values()->all(),
        ]);

        $categories = $roots->map(fn (ArtCategoryModel $cat) => [
            'slug' => $cat->slug,
            'name' => $cat->name,
            'subcategories' => $cat->children->map(fn (ArtCategoryModel $sub) => [
                'slug' => $sub->slug,
                'name' => $sub->name,
            ])->values()->all(),
        ])->all();

        return response()->json([
            'data' => $list,
            'categories' => $categories,
        ]);
    }
}
