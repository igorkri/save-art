<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Enums\Region;
use App\Http\Controllers\Controller;
use App\Models\ArtCategory as ArtCategoryModel;
use App\Models\Parameter;
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

    /**
     * Отримати список регіонів
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/regions",
     *     summary="Список регіонів",
     *     description="Повертає список всіх регіонів з перекладами",
     *     operationId="artUaInfoGetRegions",
     *     tags={"Catalog"},
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"}, default="uk")),
     *
     *     @OA\Response(response=200, description="Список регіонів")
     * )
     */
    public function regions(): JsonResponse
    {
        $language = request('language', 'uk');

        return response()->json([
            'data' => collect(Region::cases())->map(fn (Region $region) => [
                'value' => $region->value,
                'label' => $region->getLabel()[$language] ?? $region->getLabel()['uk'],
            ]),
        ]);
    }

    /**
     * Отримати список характеристик (параметрів) для категорії мистецтва
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/parameters",
     *     summary="Список характеристик категорії мистецтва",
     *     description="Повертає список характеристик (Parameter) та їх значень (ParameterValue) для побудови фільтрів або форми проєкту.",
     *     operationId="artUaInfoGetParameters",
     *     tags={"Catalog"},
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="art_category", in="query", required=true, description="Slug кореневої категорії мистецтва", @OA\Schema(type="string"), example="literature"),
     *     @OA\Parameter(name="art_subcategory", in="query", description="Slug підкатегорії", @OA\Schema(type="string"), example="prose"),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(response=200, description="Список характеристик")
     * )
     */
    public function parameters(): JsonResponse
    {
        $language = request('language');
        $language = in_array($language, ['uk', 'en'], true) ? $language : null;

        $categoryId = ArtCategoryModel::resolveIdFromSlugs(
            request('art_category'),
            request('art_subcategory')
        );

        if (! $categoryId) {
            return response()->json(['data' => []]);
        }

        $parameters = Parameter::query()
            ->where('art_category_id', $categoryId)
            ->orderBy('sort_order')
            ->with('values')
            ->get();

        return response()->json([
            'data' => $parameters->map(fn (Parameter $parameter) => [
                'id' => $parameter->id,
                'name' => $parameter->name,
                'type' => $parameter->type->value,
                'values' => $parameter->values->map(fn ($value) => [
                    'id' => $value->id,
                    'value' => $value->value,
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }
}
