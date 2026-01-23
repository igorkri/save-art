<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentResource;
use App\Models\Content;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Content",
 *     description="API для сторінок контенту (Спецпроекти та інші)"
 * )
 */
class ContentController extends Controller
{
    /**
     * Отримати список всіх активних сторінок контенту
     *
     * @OA\Get(
     *     path="/content",
     *     operationId="getContentList",
     *     tags={"Content"},
     *     summary="Список сторінок контенту",
     *     description="Повертає список всіх активних сторінок контенту, включаючи Спецпроекти та інші статичні сторінки",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успішне отримання списку",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Content list retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(type="object",
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="page_title", type="object",
     *                         @OA\Property(property="uk", type="string"),
     *                         @OA\Property(property="en", type="string")
     *                     ),
     *                     @OA\Property(property="title", type="object",
     *                         @OA\Property(property="uk", type="string"),
     *                         @OA\Property(property="en", type="string")
     *                     ),
     *                     @OA\Property(property="slug", type="string", example="special-projects"),
     *                     @OA\Property(property="content", type="object"),
     *                     @OA\Property(property="meta_title", type="object"),
     *                     @OA\Property(property="meta_description", type="object"),
     *                     @OA\Property(property="meta_keywords", type="object"),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $contents = Content::where('is_active', true)->get();
        $data = ContentResource::collection($contents)->toArray(request());

        return response()->json([
            'result' => true,
            'message' => 'Content list retrieved successfully',
            'data' => $data,
        ]);
    }

    /**
     * Отримати сторінку контенту за slug
     *
     * @OA\Get(
     *     path="/content/{slug}",
     *     operationId="getContentBySlug",
     *     tags={"Content"},
     *     summary="Сторінка контенту за slug",
     *     description="Повертає дані сторінки контенту за вказаним slug (наприклад, special-projects для Спецпроектів)",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Унікальний slug сторінки (наприклад: special-projects)",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успішне отримання даних",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Content retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="page_title", type="object"),
     *                 @OA\Property(property="title", type="object"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="content", type="object"),
     *                 @OA\Property(property="meta_title", type="object"),
     *                 @OA\Property(property="meta_description", type="object"),
     *                 @OA\Property(property="meta_keywords", type="object"),
     *                 @OA\Property(property="is_active", type="boolean")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Контент не знайдено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Content not found"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
    public function show(string $slug): JsonResponse
    {
        $content = Content::where('slug', $slug)->first();

        if (! $content) {
            return response()->json([
                'result' => false,
                'message' => 'Content not found',
                'data' => null,
            ], 404);
        }

        $data = (new ContentResource($content))->toArray(request());

        return response()->json([
            'result' => true,
            'message' => 'Content retrieved successfully',
            'data' => $data,
        ]);
    }

    /**
     * Отримати сторінку контенту за slug та мовою
     *
     * @OA\Get(
     *     path="/content/{slug}/{language}",
     *     operationId="getContentBySlugAndLanguage",
     *     tags={"Content"},
     *     summary="Сторінка контенту за slug та мовою",
     *     description="Повертає дані сторінки контенту за вказаним slug з фільтрацією мультимовного контенту за вказаною мовою",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Унікальний slug сторінки",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="language",
     *         in="path",
     *         required=true,
     *         description="Код мови (uk або en)",
     *
     *         @OA\Schema(type="string", enum={"uk", "en"}, default="uk")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успішне отримання даних",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Content retrieved successfully"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="language", type="string", example="uk")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Контент не знайдено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Content not found"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
    public function showByLanguage(string $slug, string $language = 'uk'): JsonResponse
    {
        $content = Content::where('slug', $slug)->where('is_active', true)->first();

        if (! $content) {
            return response()->json([
                'result' => false,
                'message' => 'Content not found',
                'data' => null,
            ], 404);
        }

        $supported = ['uk', 'en'];
        if (! in_array($language, $supported)) {
            $language = 'uk';
        }

        $data = (new ContentResource($content))->toArray(request());
        $filtered = $this->filterByLanguage($data, $language);

        return response()->json([
            'result' => true,
            'message' => 'Content retrieved successfully',
            'data' => $filtered,
            'language' => $language,
        ]);
    }

    /**
     * Отримати контент за мовою
     *
     * @OA\Get(
     *     path="/content/language/{language}",
     *     operationId="getContentByLanguage",
     *     tags={"Content"},
     *     summary="Контент за мовою",
     *     description="Повертає перший активний контент з фільтрацією мультимовного контенту за вказаною мовою",
     *
     *     @OA\Parameter(
     *         name="language",
     *         in="path",
     *         required=true,
     *         description="Код мови (uk або en)",
     *
     *         @OA\Schema(type="string", enum={"uk", "en"}, default="uk")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успішне отримання даних",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Content retrieved successfully"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="language", type="string", example="uk")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Контент не знайдено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Content not found"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
    public function getByLanguage(string $language = 'uk'): JsonResponse
    {
        $content = Content::where('is_active', true)->first();

        if (! $content) {
            return response()->json([
                'result' => false,
                'message' => 'Content not found',
                'data' => null,
            ], 404);
        }

        $supported = ['uk', 'en'];
        if (! in_array($language, $supported)) {
            $language = 'uk';
        }

        $data = (new ContentResource($content))->toArray(request());
        $filtered = $this->filterByLanguage($data, $language);

        return response()->json([
            'result' => true,
            'message' => 'Content retrieved successfully',
            'data' => $filtered,
            'language' => $language,
        ]);
    }

    /**
     * Filter multilingual content fields by language.
     */
    private function filterByLanguage(array $data, string $language): array
    {
        $fields = ['page_title', 'title', 'content', 'meta_title', 'meta_description', 'meta_keywords'];

        foreach ($fields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = $this->extractLanguageContent($data[$field], $language);
            }
        }

        return $data;
    }

    /**
     * Extract content for a specific language.
     */
    private function extractLanguageContent($content, string $language)
    {
        if (! is_array($content)) {
            return $content;
        }

        if (isset($content[$language])) {
            return $content[$language];
        }

        $extracted = [];
        foreach ($content as $key => $value) {
            if (is_array($value)) {
                if (isset($value[$language])) {
                    $extracted[$key] = $value[$language];
                } else {
                    $extracted[$key] = $this->extractLanguageContent($value, $language);
                }
            } else {
                $extracted[$key] = $value;
            }
        }

        return $extracted;
    }
}
