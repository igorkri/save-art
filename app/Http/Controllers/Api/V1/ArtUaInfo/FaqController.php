<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

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
     *     path="/v1/art-ua-info/faq",
     *     operationId="artUaInfoGetFaq",
     *     tags={"FAQ"},
     *     summary="Список FAQ",
     *     description="Повертає всі FAQ категорії з питаннями та відповідями.",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Response(response=200, description="Список FAQ")
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
     *     path="/v1/art-ua-info/faq/category/{slug}",
     *     operationId="artUaInfoGetFaqCategory",
     *     tags={"FAQ"},
     *     summary="FAQ категорії",
     *     description="Повертає питання конкретної FAQ категорії.",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Slug категорії", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="FAQ категорії"),
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
