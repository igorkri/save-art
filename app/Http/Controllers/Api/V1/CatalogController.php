<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Region;
use App\Http\Controllers\Controller;
use App\Models\ArtCategory as ArtCategoryModel;
use App\Models\Parameter;
use Illuminate\Http\JsonResponse;

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
     *     path="/v1/categories",
     *     summary="Список категорій мистецтва",
     *     description="Повертає список всіх категорій мистецтва з перекладами та підкатегоріями",
     *     operationId="getCategories",
     *     tags={"Catalog"},
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(
     *         name="language",
     *         in="query",
     *         description="Мова відповіді (uk, en). Якщо не вказано — uk за замовчуванням",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"uk", "en"}, default="uk")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список категорій",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="value", type="string", example="scenic"),
     *                     @OA\Property(property="label", type="string", example="Сценічне мистецтво"),
     *                     @OA\Property(
     *                         property="subcategories",
     *                         type="array",
     *
     *                         @OA\Items(
     *
     *                             @OA\Property(property="value", type="string", example="directing"),
     *                             @OA\Property(property="label", type="string", example="Режисура")
     *                         )
     *                     )
     *                 )
     *             ),
     *             example={
     *                 "data": {
     *                     {
     *                         "value": "scenic",
     *                         "label": "Сценічне мистецтво",
     *                         "subcategories": {
     *                             {"value": "directing", "label": "Режисура"},
     *                             {"value": "acting", "label": "Акторське мистецтво"},
     *                             {"value": "choreography", "label": "Хореографічне мистецтво"},
     *                             {"value": "original_genre", "label": "Оригінальний жанр"}
     *                         }
     *                     },
     *                     {
     *                         "value": "visual",
     *                         "label": "Візуальне мистецтво",
     *                         "subcategories": {
     *                             {"value": "photography", "label": "Художня фотографія"},
     *                             {"value": "video", "label": "Відеозйомка та монтаж"},
     *                             {"value": "cinema", "label": "Повнометражний кінематограф"},
     *                             {"value": "ar", "label": "Доповнена реальність"}
     *                         }
     *                     },
     *                     {
     *                         "value": "fine_art",
     *                         "label": "Образотворче мистецтво",
     *                         "subcategories": {
     *                             {"value": "painting", "label": "Живопис"},
     *                             {"value": "sculpture", "label": "Скульптура"},
     *                             {"value": "digital", "label": "Діджитал"}
     *                         }
     *                     },
     *                     {
     *                         "value": "literature",
     *                         "label": "Література",
     *                         "subcategories": {
     *                             {"value": "poetry", "label": "Поезія"},
     *                             {"value": "prose", "label": "Проза"}
     *                         }
     *                     },
     *                     {
     *                         "value": "music",
     *                         "label": "Музичне мистецтво",
     *                         "subcategories": {}
     *                     },
     *                     {
     *                         "value": "other",
     *                         "label": "Інше",
     *                         "subcategories": {}
     *                     }
     *                 }
     *             }
     *         )
     *     )
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
     *     path="/v1/regions",
     *     summary="Список регіонів",
     *     description="Повертає список всіх регіонів з перекладами",
     *     operationId="getRegions",
     *     tags={"Catalog"},
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(
     *         name="language",
     *         in="query",
     *         description="Мова відповіді (uk, en). Якщо не вказано — uk за замовчуванням",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"uk", "en"}, default="uk")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список регіонів",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="value", type="string", example="europe"),
     *                     @OA\Property(property="label", type="string", example="Європа")
     *                 )
     *             ),
     *             example={
     *                 "data": {
     *                     {"value": "europe", "label": "Європа"},
     *                     {"value": "middle_east", "label": "Близький Схід"},
     *                     {"value": "asia", "label": "Азія"},
     *                     {"value": "africa", "label": "Африка"},
     *                     {"value": "north_america", "label": "Північна Америка"},
     *                     {"value": "south_america", "label": "Південна Америка"},
     *                     {"value": "oceania", "label": "Океанія"}
     *                 }
     *             }
     *         )
     *     )
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
     *     path="/v1/parameters",
     *     summary="Список характеристик категорії мистецтва",
     *     description="Повертає список характеристик (Parameter) та їх значень (ParameterValue) для побудови фільтрів або форми проєкту. Категорія визначається за art_category (кореневий slug) та опційно art_subcategory (slug підкатегорії) — якщо підкатегорія не вказана або не має власних характеристик, повертаються характеристики кореневої категорії.",
     *     operationId="getParameters",
     *     tags={"Catalog"},
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="art_category", in="query", required=true, description="Slug кореневої категорії мистецтва", @OA\Schema(type="string"), example="literature"),
     *     @OA\Parameter(name="art_subcategory", in="query", description="Slug підкатегорії", @OA\Schema(type="string"), example="prose"),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список характеристик",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="Жанр"),
     *                     @OA\Property(property="type", type="string", enum={"list", "custom"}, example="list"),
     *                     @OA\Property(
     *                         property="values",
     *                         type="array",
     *
     *                         @OA\Items(
     *
     *                             @OA\Property(property="id", type="integer", example=12),
     *                             @OA\Property(property="value", type="string", example="Роман")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     )
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
