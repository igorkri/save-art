<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArtistBoardResource;
use App\Models\ArtistBoard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Artist Board",
 *     description="API для дошки художників (10 художників в 10 національних музеях світу)"
 * )
 *
 * Контроллер для API дошки художників.
 *
 * Структура поля titles включає:
 * - title1: Перший заголовок (мультимовний)
 * - title2: Другий заголовок (мультимовний)
 * - description: Короткий опис спецпроєкту (мультимовний) - додано в Swagger документацію
 */
class ArtistBoardController extends Controller
{
    /**
     * Отримати дані Artist Board
     *
     * @OA\Get(
     *     path="/artist-board",
     *     operationId="getArtistBoard",
     *     tags={"Artist Board"},
     *     summary="Дошка художників (10 художників в 10 національних музеях світу)",
     *     description="Повертає дані про спецпроект '10 художників в 10 національних музеях світу'. Включає інформацію про художників, їх виставки, музеї та роботи. Якщо вказано параметр language - повертає контент лише для вказаної мови, інакше повертає всі мовні версії.",
     *
     *     @OA\Parameter(
     *         name="language",
     *         in="query",
     *         required=false,
     *         description="Код мови (uk або en). Якщо не вказано - повертає всі мовні версії.",
     *
     *         @OA\Schema(type="string", enum={"uk", "en"})
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Успішне отримання даних",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="ArtistBoard data retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="titles", type="object",
     *                     @OA\Property(property="title1", type="object", example={"uk": "Спецпроєкт", "en": "Special Project"}),
     *                     @OA\Property(property="title2", type="object", example={"uk": "10 художників в 10 національних музеях світу", "en": "10 artists in 10 national museums of the world"}),
     *                     @OA\Property(property="description", type="object", example={"uk": "Короткий опис спецпроєкту українською", "en": "Short description of the special project in English"})
     *                 ),
     *                 @OA\Property(property="logo_museums", type="array",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="logo_museum", type="string", example="https://example.com/storage/artist-boards/logos/museum1.jpg")
     *                     )
     *                 ),
     *                 @OA\Property(property="descriptions", type="object", example={"uk": "<p>Опис спецпроєкту...</p>", "en": "<p>Special project description...</p>"}),
     *                 @OA\Property(property="data", type="array",
     *
     *                     @OA\Items(type="object", example={"image": "https://example.com/storage/artist-boards/artists/artist1.jpg", "name": {"uk": "Іван Петренко", "en": "Ivan Petrenko"}, "exhibition_link": "https://museum.com/exhibition", "facebook_link": "https://facebook.com/artist", "museums": {{"name": {"uk": "Національний музей мистецтв", "en": "National Museum of Arts"}, "exhibition_name": {"uk": "Виставка сучасного мистецтва", "en": "Contemporary Art Exhibition"}, "dates": "01.01.2024 - 01.03.2024"}}, "works": {{"title": {"uk": "Назва роботи", "en": "Work Title"}, "description": {"uk": "<p>Опис роботи...</p>", "en": "<p>Work description...</p>"}, "image": "https://example.com/storage/artist-boards/works/work1.jpg"}}})
     *                 ),
     *
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="language", type="string", nullable=true, example="uk", description="Вказана мова (якщо був переданий параметр)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Дані не знайдено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="ArtistBoard data not found"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $artistBoard = ArtistBoard::first();

        if (! $artistBoard) {
            return response()->json([
                'result' => false,
                'message' => 'ArtistBoard data not found',
                'data' => null,
            ], 404);
        }

        $resource = new ArtistBoardResource($artistBoard);
        $data = $resource->toArray($request);

        // Check if language parameter is provided
        $language = $request->query('language');
        $response = [
            'result' => true,
            'message' => 'ArtistBoard data retrieved successfully',
        ];

        if ($language) {
            // Validate language
            $supportedLanguages = ['uk', 'en'];
            if (! in_array($language, $supportedLanguages)) {
                $language = 'uk';
            }

            // Filter data by language
            $data = $this->filterByLanguage($data, $language);

            $response['language'] = $language;
        }

        $response['data'] = $data;

        return response()->json($response);
    }

    /**
     * Filter multilingual content by specified language
     */
    private function filterByLanguage(array $data, string $language): array
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

    /**
     * Extract content for specific language from multilingual array
     *
     * Handles different structures:
     * 1. Simple language array: ['uk' => 'text', 'en' => 'text'] -> returns 'text' for requested language
     * 2. Mixed array: ['uk' => [...], 'en' => [...], 'image' => '...'] -> keeps only requested language key
     * 3. Nested objects with language values inside: recursively processes
     */
    private function extractLanguageContent($content, string $language)
    {
        if (! is_array($content)) {
            return $content;
        }

        $supportedLanguages = ['uk', 'en'];

        // Check what type of keys we have
        $languageKeys = [];
        $nonLanguageKeys = [];

        foreach (array_keys($content) as $key) {
            if (in_array($key, $supportedLanguages, true)) {
                $languageKeys[] = $key;
            } else {
                $nonLanguageKeys[] = $key;
            }
        }

        // Case 1: Only language keys - return the value for requested language
        if (! empty($languageKeys) && empty($nonLanguageKeys)) {
            $value = $content[$language] ?? $content['uk'] ?? reset($content);

            // Recursively process if the value is also an array
            return is_array($value) ? $this->extractLanguageContent($value, $language) : $value;
        }

        // Case 2 & 3: Process all keys, removing unwanted language keys
        $extracted = [];

        foreach ($content as $key => $value) {
            // Skip other languages (not the requested one)
            if (in_array($key, $supportedLanguages, true) && $key !== $language) {
                continue;
            }

            // Recursively process arrays
            if (is_array($value)) {
                $extracted[$key] = $this->extractLanguageContent($value, $language);
            } else {
                $extracted[$key] = $value;
            }
        }

        return $extracted;
    }
}
