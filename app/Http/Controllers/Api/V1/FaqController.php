<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FaqCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="FAQ",
 *     description="API для роботи з FAQ"
 * )
 */
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
     *     description="Повертає всі FAQ категорії з питаннями та відповідями.",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список FAQ",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FAQ data retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="categories", type="array",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Загальні питання"),
     *                         @OA\Property(property="slug", type="string", example="general"),
     *                         @OA\Property(property="questions", type="array",
     *
     *                             @OA\Items(type="object",
     *
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="question", type="string", example="Як це працює?"),
     *                                 @OA\Property(property="answer", type="string", example="Відповідь...")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $data = Cache::remember('faq_all', 3600, function () {
            return $this->getAllFaq();
        });

        return response()->json([
            'result' => true,
            'message' => 'FAQ data retrieved successfully',
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
     *     description="Повертає питання конкретної FAQ категорії.",
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
     *             @OA\Property(property="message", type="string", example="FAQ category retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="category", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Загальні питання"),
     *                     @OA\Property(property="slug", type="string", example="general"),
     *                     @OA\Property(property="questions", type="array", @OA\Items(type="object"))
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Категорію не знайдено")
     * )
     */
    public function category(Request $request, string $slug): JsonResponse
    {
        $category = FaqCategory::query()
            ->with(['faqs' => fn ($q) => $q->active()->orderBy('order')])
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        $categoryData = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'questions' => $category->faqs->map(fn ($faq) => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
            ])->toArray(),
        ];

        return response()->json([
            'result' => true,
            'message' => 'FAQ category retrieved successfully',
            'data' => ['category' => $categoryData],
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
