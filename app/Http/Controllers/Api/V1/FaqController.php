<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FaqCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FaqController extends Controller
{
    /**
     * Отримати всі FAQ категорії з питаннями
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
