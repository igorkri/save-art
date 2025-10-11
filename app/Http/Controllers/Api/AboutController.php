<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AboutResource;
use App\Models\About;
use Illuminate\Http\JsonResponse;

class AboutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $about = About::first();

        if (! $about) {
            return response()->json([
                'result' => false,
                'message' => 'About data not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'result' => true,
            'message' => 'About data retrieved successfully',
            'data' => new AboutResource($about),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $about = About::find($id);

        if (! $about) {
            return response()->json([
                'result' => false,
                'message' => 'About data not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'result' => true,
            'message' => 'About data retrieved successfully',
            'data' => new AboutResource($about),
        ]);
    }

    /**
     * Get about data by language
     */
    public function getByLanguage(string $language = 'uk'): JsonResponse
    {
        $about = About::first();

        if (! $about) {
            return response()->json([
                'result' => false,
                'message' => 'About data not found',
                'data' => null,
            ], 404);
        }

        // Validate language
        $supportedLanguages = ['uk', 'en'];
        if (! in_array($language, $supportedLanguages)) {
            $language = 'uk'; // default to Ukrainian
        }

        $resource = new AboutResource($about);
        $data = $resource->toArray(request());

        // Filter multilingual content by language
        $filteredData = $this->filterByLanguage($data, $language);

        return response()->json([
            'result' => true,
            'message' => 'About data retrieved successfully',
            'data' => $filteredData,
            'language' => $language,
        ]);
    }

    /**
     * Filter multilingual content by specified language
     */
    private function filterByLanguage(array $data, string $language): array
    {
        $multilingualFields = ['title', 'feats', 'description', 'goals', 'tasks', 'implementation', 'results', 'id_art', 'events', 'project'];

        foreach ($multilingualFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = $this->extractLanguageContent($data[$field], $language);
            }
        }

        return $data;
    }

    /**
     * Extract content for specific language from multilingual array
     */
    private function extractLanguageContent($content, string $language)
    {
        if (! is_array($content)) {
            return $content;
        }

        // If this is a direct language array (e.g., ['uk' => 'text', 'en' => 'text'])
        if (isset($content[$language])) {
            return $content[$language];
        }

        // If this is a nested array, recursively process each element
        $extracted = [];
        foreach ($content as $key => $value) {
            if (is_array($value)) {
                // Check if this is a language-specific array
                if (isset($value[$language])) {
                    $extracted[$key] = $value[$language];
                } else {
                    // Recursively process nested arrays
                    $extracted[$key] = $this->extractLanguageContent($value, $language);
                }
            } else {
                // Non-array values (like images, booleans) pass through unchanged
                $extracted[$key] = $value;
            }
        }

        return $extracted;
    }
}
