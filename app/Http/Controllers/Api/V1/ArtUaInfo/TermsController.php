<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Http\Controllers\Controller;
use App\Models\TermsSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Terms",
 *     description="API для роботи з умовами використання"
 * )
 */
class TermsController extends Controller
{
    /**
     * Отримати умови використання
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/terms",
     *     operationId="artUaInfoGetTerms",
     *     tags={"Terms"},
     *     summary="Умови використання",
     *     description="Повертає всі розділи умов використання з блоками контенту.",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="language", in="query", description="Код мови (uk або en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(response=200, description="Умови використання")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $language = $request->query('language');

        $cacheKey = $language ? "terms_all_{$language}" : 'terms_all';
        $data = Cache::remember($cacheKey, 3600, function () {
            return $this->getAllTerms();
        });

        $response = [
            'result' => true,
            'message' => 'Terms of use data retrieved successfully',
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
     * @return array<string, mixed>
     */
    private function getAllTerms(): array
    {
        $sections = TermsSection::query()
            ->with(['blocks'])
            ->active()
            ->orderBy('order')
            ->get();

        return [
            'sections' => $sections->map(fn ($section) => [
                'id' => $section->id,
                'heading' => $section->heading,
                'date' => $section->date,
                'blocks' => $section->blocks->map(fn ($block) => [
                    'id' => $block->id,
                    'heading' => $block->heading,
                    'paragraphs' => $block->paragraphs,
                    'list' => $block->list_type ? [
                        'type' => $block->list_type,
                        'items' => $block->list_items,
                    ] : null,
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
        if (isset($data['sections']) && is_array($data['sections'])) {
            $data['sections'] = array_map(function ($section) use ($language) {
                return $this->filterSectionByLanguage($section, $language);
            }, $data['sections']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private function filterSectionByLanguage(array $section, string $language): array
    {
        if (isset($section['heading']) && is_array($section['heading'])) {
            $section['heading'] = $section['heading'][$language] ?? $section['heading']['uk'] ?? null;
        }

        if (isset($section['blocks']) && is_array($section['blocks'])) {
            $section['blocks'] = array_map(function ($block) use ($language) {
                return $this->filterBlockByLanguage($block, $language);
            }, $section['blocks']);
        }

        return $section;
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    private function filterBlockByLanguage(array $block, string $language): array
    {
        if (isset($block['heading']) && is_array($block['heading'])) {
            $block['heading'] = $block['heading'][$language] ?? $block['heading']['uk'] ?? null;
        }

        if (isset($block['paragraphs']) && is_array($block['paragraphs'])) {
            $block['paragraphs'] = $block['paragraphs'][$language] ?? $block['paragraphs']['uk'] ?? null;
        }

        if (isset($block['list']) && is_array($block['list']) && isset($block['list']['items']) && is_array($block['list']['items'])) {
            $block['list']['items'] = $block['list']['items'][$language] ?? $block['list']['items']['uk'] ?? null;
        }

        return $block;
    }
}
