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
 * - title1: Перший заголовок
 * - title2: Другий заголовок
 * - description: Короткий опис спецпроєкту
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
     *     description="Повертає дані про спецпроект '10 художників в 10 національних музеях світу'. Включає інформацію про художників, їх виставки, музеї та роботи.",
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
     *                     @OA\Property(property="title1", type="string", example="Спецпроєкт"),
     *                     @OA\Property(property="title2", type="string", example="10 художників в 10 національних музеях світу"),
     *                     @OA\Property(property="description", type="string", example="Короткий опис спецпроєкту")
     *                 ),
     *                 @OA\Property(property="logo_museums", type="array",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="logo_museum", type="string", example="https://example.com/storage/artist-boards/logos/museum1.jpg")
     *                     )
     *                 ),
     *                 @OA\Property(property="descriptions", type="string", example="<p>Опис спецпроєкту...</p>"),
     *                 @OA\Property(property="data", type="array",
     *
     *                     @OA\Items(type="object", example={"image": "https://example.com/storage/artist-boards/artists/artist1.jpg", "name": "Іван Петренко", "exhibition_link": "https://museum.com/exhibition", "facebook_link": "https://facebook.com/artist", "museums": {{"name": "Національний музей мистецтв", "exhibition_name": "Виставка сучасного мистецтва", "dates": "01.01.2024 - 01.03.2024"}}, "works": {{"title": "Назва роботи", "description": "<p>Опис роботи...</p>", "image": "https://example.com/storage/artist-boards/works/work1.jpg"}}})
     *                 ),
     *
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
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

        return response()->json([
            'result' => true,
            'message' => 'ArtistBoard data retrieved successfully',
            'data' => $resource->toArray($request),
        ]);
    }
}
