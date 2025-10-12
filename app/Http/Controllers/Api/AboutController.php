<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AboutResource;
use App\Http\Resources\ArtistBoardResource;
use App\Models\About;
use App\Models\ArtistBoard;
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

        $resource = new AboutResource($about);
        $data = $resource->toArray(request());

        // Add ArtistBoard data if is_active_artist is true
        if ($about->is_active_artist) {
            $artistBoard = ArtistBoard::first();
            if ($artistBoard) {
                $data['artist_board'] = new ArtistBoardResource($artistBoard);
            }
        }

        return response()->json([
            'result' => true,
            'message' => 'About data retrieved successfully',
            'data' => $data,
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

        $resource = new AboutResource($about);
        $data = $resource->toArray(request());

        // Add ArtistBoard data if is_active_artist is true
        if ($about->is_active_artist) {
            $artistBoard = ArtistBoard::first();
            if ($artistBoard) {
                $data['artist_board'] = new ArtistBoardResource($artistBoard);
            }
        }

        return response()->json([
            'result' => true,
            'message' => 'About data retrieved successfully',
            'data' => $data,
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

        // Add ArtistBoard data if is_active_artist is true
        if ($about->is_active_artist) {
            $artistBoard = ArtistBoard::first();
            if ($artistBoard) {
                $artistResource = new ArtistBoardResource($artistBoard);
                $artistData = $artistResource->toArray(request());

                // Filter artist board data by language
                $filteredData['artist_board'] = $this->filterArtistBoardByLanguage($artistData, $language);
            }
        }

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

    /**
     * Filter ArtistBoard multilingual content by specified language
     */
    private function filterArtistBoardByLanguage(array $data, string $language): array
    {
        // Filter titles
        if (isset($data['titles']) && is_array($data['titles'])) {
            $data['titles'] = $this->extractLanguageContent($data['titles'], $language);
        }

        // Filter descriptions
        if (isset($data['descriptions']) && is_array($data['descriptions'])) {
            $data['descriptions'] = $this->extractLanguageContent($data['descriptions'], $language);
        }

        // Filter artist data
        if (isset($data['data']) && is_array($data['data'])) {
            $data['data'] = array_map(function ($artist) use ($language) {
                // Filter artist name
                if (isset($artist['name'])) {
                    $artist['name'] = $this->extractLanguageContent($artist['name'], $language);
                }

                // Filter museums
                if (isset($artist['museums']) && is_array($artist['museums'])) {
                    $artist['museums'] = array_map(function ($museum) use ($language) {
                        if (isset($museum['name'])) {
                            $museum['name'] = $this->extractLanguageContent($museum['name'], $language);
                        }
                        if (isset($museum['exhibition_name'])) {
                            $museum['exhibition_name'] = $this->extractLanguageContent($museum['exhibition_name'], $language);
                        }

                        return $museum;
                    }, $artist['museums']);
                }

                // Filter works
                if (isset($artist['works']) && is_array($artist['works'])) {
                    $artist['works'] = array_map(function ($work) use ($language) {
                        if (isset($work['title'])) {
                            $work['title'] = $this->extractLanguageContent($work['title'], $language);
                        }
                        if (isset($work['description'])) {
                            $work['description'] = $this->extractLanguageContent($work['description'], $language);
                        }

                        return $work;
                    }, $artist['works']);
                }

                return $artist;
            }, $data['data']);
        }

        return $data;
    }
}
