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
     *     @OA\Parameter(name="language", in="query", description="Код мови (uk або en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(response=200, description="Список FAQ")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $language = $request->query('language');

        $cacheKey = $language ? "faq_all_{$language}" : 'faq_all';
        $data = Cache::remember($cacheKey, 3600, function () {
            return $this->getAllFaq();
        });

        $response = [
            'result' => true,
            'message' => 'FAQ data retrieved successfully',
        ];

        if ($language) {
            $supportedLanguages = ['uk', 'en'];
            if (! in_array($language, $supportedLanguages)) {
                $language = 'uk';
            }

            $data = $this->filterByLanguage($data, $language);
            $response['language'] = $language;
        }

        $response['data'] = $data;

        return response()->json($response);
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
     *     @OA\Parameter(name="language", in="query", description="Код мови (uk або en)", @OA\Schema(type="string", enum={"uk", "en"})),
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

        $response = [
            'result' => true,
            'message' => 'FAQ category retrieved successfully',
        ];

        $language = $request->query('language');
        if ($language) {
            $supportedLanguages = ['uk', 'en'];
            if (! in_array($language, $supportedLanguages)) {
                $language = 'uk';
            }

            $categoryData = $this->filterCategoryByLanguage($categoryData, $language);
            $response['language'] = $language;
        }

        $response['data'] = ['category' => $categoryData];

        return response()->json($response);
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function filterByLanguage(array $data, string $language): array
    {
        if (isset($data['categories']) && is_array($data['categories'])) {
            $data['categories'] = array_map(function ($category) use ($language) {
                return $this->filterCategoryByLanguage($category, $language);
            }, $data['categories']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $category
     * @return array<string, mixed>
     */
    private function filterCategoryByLanguage(array $category, string $language): array
    {
        if (isset($category['name']) && is_array($category['name'])) {
            $category['name'] = $category['name'][$language] ?? $category['name']['uk'] ?? '';
        }

        if (isset($category['questions']) && is_array($category['questions'])) {
            $category['questions'] = array_map(function ($question) use ($language) {
                if (isset($question['question']) && is_array($question['question'])) {
                    $question['question'] = $question['question'][$language] ?? $question['question']['uk'] ?? '';
                }
                if (isset($question['answer']) && is_array($question['answer'])) {
                    $question['answer'] = $question['answer'][$language] ?? $question['answer']['uk'] ?? '';
                }

                return $question;
            }, $category['questions']);
        }

        return $category;
    }
}
