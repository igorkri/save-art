<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FaqCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;

class FaqController extends Controller
{
    /**
     * Отримати всі FAQ категорії з питаннями
     *
     * @OA\Get(
     *     path="/v1/faq",
     *     operationId="getFaq",
     *     tags={"FAQ"},
     *     summary="Список FAQ",
     *     description="Повертає всі FAQ категорії з питаннями та відповідями",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="language", in="query", description="Мова (uk, en)", @OA\Schema(type="string", default="uk")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список FAQ",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="categories", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $language = $request->input('language', 'uk');

        $cacheKey = "faq_all_{$language}";
        $data = Cache::remember($cacheKey, 3600, function () {
            return $this->getAllFaq();
        });

        return response()->json([
            'result' => true,
            'data' => $data,
        ]);
    }

    /**
     * Отримати FAQ по мові (альтернативний endpoint)
     *
     * @OA\Get(
     *     path="/v1/faq/{language}",
     *     operationId="getFaqByLanguage",
     *     tags={"FAQ"},
     *     summary="FAQ по мові",
     *     description="Повертає FAQ для вказаної мови",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="language", in="path", required=true, description="Код мови (uk, en)", @OA\Schema(type="string")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список FAQ",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function byLanguage(string $language): JsonResponse
    {
        $cacheKey = "faq_all_{$language}";
        $data = Cache::remember($cacheKey, 3600, function () {
            return $this->getAllFaq();
        });

        return response()->json([
            'result' => true,
            'data' => $data,
        ]);
    }

    /**
     * Отримати FAQ конкретної категорії
     *
     * @OA\Get(
     *     path="/v1/faq/category/{slug}",
     *     operationId="getFaqCategory",
     *     tags={"FAQ"},
     *     summary="FAQ категорії",
     *     description="Повертає питання конкретної FAQ категорії",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Slug категорії", @OA\Schema(type="string")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="FAQ категорії",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="category", type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Категорію не знайдено")
     * )
     */
    public function category(string $slug): JsonResponse
    {
        $category = FaqCategory::query()
            ->with(['faqs' => fn ($q) => $q->active()->orderBy('order')])
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'result' => true,
            'data' => [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'questions' => $category->faqs->map(fn ($faq) => [
                        'id' => $faq->id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                    ])->toArray(),
                ],
            ],
        ]);
    }

    /**
     * Отримати всі FAQ дані
     *
     * @return array<string, mixed>
     */
    private function getAllFaq(): array
    {
        $categories = FaqCategory::query()
            ->with(['faqs' => fn ($q) => $q->active()->orderBy('order')])
            ->active()
            ->orderBy('order')
            ->get();

        return [
            'categories' => $categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'questions' => $category->faqs->map(fn ($faq) => [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                ])->toArray(),
            ])->toArray(),
        ];
    }
}
