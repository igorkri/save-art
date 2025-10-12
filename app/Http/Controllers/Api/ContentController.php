<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentResource;
use App\Models\Content;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    /**
     * Display a listing of content.
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
     * Display the specified content item by slug.
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
     * Display the specified content item by slug and filter by language.
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
     * Get content by language.
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
