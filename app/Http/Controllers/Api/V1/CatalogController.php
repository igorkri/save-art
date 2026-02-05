<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ArtCategory;
use App\Enums\Region;
use App\Http\Controllers\Controller;
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
     *         name="lang",
     *         in="query",
     *         description="Мова відповіді (uk, en)",
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
        $language = request('lang', 'uk');

        return response()->json([
            'data' => collect(ArtCategory::cases())->map(fn (ArtCategory $cat) => [
                'value' => $cat->value,
                'label' => $cat->getLabel($language),
                'subcategories' => collect($cat->getSubcategoriesWithTranslations())->map(fn ($translations, $value) => [
                    'value' => $value,
                    'label' => $translations[$language] ?? $translations['uk'],
                ])->values(),
            ]),
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
     *         name="lang",
     *         in="query",
     *         description="Мова відповіді (uk, en)",
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
        $language = request('lang', 'uk');

        return response()->json([
            'data' => collect(Region::cases())->map(fn (Region $region) => [
                'value' => $region->value,
                'label' => $region->getLabel()[$language] ?? $region->getLabel()['uk'],
            ]),
        ]);
    }
}
