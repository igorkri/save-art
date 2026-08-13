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
     *     @OA\Response(response=200, description="Умови використання")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $data = Cache::remember('terms_all', 3600, function () {
            return $this->getAllTerms();
        });

        return response()->json([
            'result' => true,
            'message' => 'Terms of use data retrieved successfully',
            'data' => $data,
        ]);
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
}
