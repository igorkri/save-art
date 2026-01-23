<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AboutResource;
use App\Http\Resources\ArtistBoardResource;
use App\Models\About;
use App\Models\ArtistBoard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="About",
 *     description="API для сторінки 'Про нас'"
 * )
 */
class AboutController extends Controller
{
    /**
     * Отримати дані сторінки "Про нас"
     *
     * @OA\Get(
     *     path="/about",
     *     operationId="getAbout",
     *     tags={"About"},
     *     summary="Дані сторінки 'Про нас'",
     *     description="Повертає всі дані для сторінки 'Про нас'. Якщо вказано параметр language - повертає контент лише для вказаної мови, інакше повертає всі мовні версії.",
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
     *             @OA\Property(property="message", type="string", example="About data retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="object", example={"uk": "Задачі проєкту", "en": "Project Tasks"}),
     *                 @OA\Property(property="feats", type="object", example={"uk": {{"name": "Екосистема", "title": "Арт-платформа для митців з України", "description": "Можливе масштабування на інші країни, які цього потребують."}}, "en": {{"name": "Ecosystem", "title": "An art platform for artists from Ukraine", "description": "Scalable to other countries that need it."}}, "feat_image": "https://example.com/storage/about/feat.webp"}),
     *                 @OA\Property(property="description", type="object", example={"uk": "творити Майбутнє вже сьогодні.", "en": "to create the Future today.", "icon": "https://example.com/storage/about/flag_ua.webp", "image": "https://example.com/storage/about/invasion.webp", "text": {"uk": "<p>Текст опису українською</p>", "en": "<p>Description text in English</p>"}, "title_date": {"uk": "24.02", "en": "24.02"}, "description_date": {"uk": "З початком повномасштабного вторгнення...", "en": "Since the beginning of the full-scale invasion..."}}),
     *                 @OA\Property(property="goals", type="object", example={"task": {"uk": "Створення арт-платформи підтримки українських митців", "en": "Creation of an art platform to support Ukrainian artists"}, "image": "https://example.com/storage/about/purpose.webp", "title": {"uk": "Основна мета проєкту", "en": "The Main Goal of the Project"}, "description": {"uk": "<p>для подолання глобальної культурної кризи...</p>", "en": "<p>to overcome the global cultural crisis...</p>"}}),
     *                 @OA\Property(property="tasks", type="object", example={"uk": {{"task": "Платформа для публікації проєкту"}, {"task": "Прямий доступ до аудиторії"}}, "en": {{"task": "A platform for publishing a project"}, {"task": "Direct access to an audience willing to support"}}}),
     *                 @OA\Property(property="implementation", type="object", example={"image": "https://example.com/storage/about/project_implementation.webp", "title": {"uk": "Реалізація проєкту", "en": "Project Implementation"}, "items": {"uk": {{"item": {"title": "Створення всеукраїнської платформи особистостей", "description": "з різних культурних сфер діяльності..."}}}, "en": {{"item": {"title": "Creation of a nationwide platform of individuals", "description": "from various cultural fields..."}}}}}),
     *                 @OA\Property(property="results", type="object", example={"title": {"uk": "Результат", "en": "Result"}, "description": {"uk": "<h6>Митці отримують грантову підтримку...</h6>", "en": "<h6>Artists receive grant support...</h6>"}}),
     *                 @OA\Property(property="id_art", type="object", example={"image": "https://example.com/storage/about/idArtUa.webp", "title": {"uk": "Благодійний фонд ID Art UA", "en": "Charitable Foundation ID Art UA"}, "description": {"uk": "<p>Транслювати події, обставини...</p>", "en": "<p>To convey events, circumstances...</p>"}}),
     *                 @OA\Property(property="events", type="object", example={"h2": {"uk": "<h2>Персональні виставки українських художників в провідних музеях світу</h2>", "en": "<h2>Solo exhibitions of Ukrainian artists in leading museums worldwide</h2>"}, "title": {"uk": "Пілотний проект", "en": "Pilot Project"}}),
     *                 @OA\Property(property="project", type="object", example={"image": "https://example.com/storage/about/project/main_project_img.webp", "image_bg": "https://example.com/storage/about/project/main_project_bg.webp", "description": {"uk": "<p>Культурна спадщина України...</p>", "en": "<p>Ukraine's cultural heritage...</p>"}}),
     *                 @OA\Property(property="artists", type="array", nullable=true, @OA\Items(type="object", example={"id": 1, "name": "Художник"})),
     *                 @OA\Property(property="is_active_artist", type="boolean", example=true),
     *                 @OA\Property(property="partners", type="array", nullable=true, @OA\Items(type="object", example={"id": 1, "name": "Партнер", "logo": "https://example.com/storage/partners/logo.webp"})),
     *                 @OA\Property(property="artist_board", type="object", nullable=true, example={"id": 1, "titles": {"uk": "Заголовок", "en": "Title"}, "descriptions": {"uk": "Опис", "en": "Description"}, "data": {}})
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
     *             @OA\Property(property="message", type="string", example="About data not found"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
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
        $data = $resource->toArray($request);

        // Add ArtistBoard data if is_active_artist is true
        if ($about->is_active_artist) {
            $artistBoard = ArtistBoard::first();
            if ($artistBoard) {
                $artistResource = new ArtistBoardResource($artistBoard);
                $artistData = $artistResource->toArray($request);
                $data['artist_board'] = $artistData;
            }
        }

        // Check if language parameter is provided
        $language = $request->query('language');
        $response = [
            'result' => true,
            'message' => 'About data retrieved successfully',
        ];

        if ($language) {
            // Validate language
            $supportedLanguages = ['uk', 'en'];
            if (! in_array($language, $supportedLanguages)) {
                $language = 'uk';
            }

            // Filter data by language
            $data = $this->filterByLanguage($data, $language);

            // Filter artist_board by language if exists
            if (isset($data['artist_board'])) {
                $data['artist_board'] = $this->filterArtistBoardByLanguage($data['artist_board'], $language);
            }

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
