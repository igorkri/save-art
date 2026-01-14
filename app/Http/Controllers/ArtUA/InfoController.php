<?php

namespace App\Http\Controllers\ArtUA;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Info",
 *     description="Загальна інформація про API"
 * )
 */
class InfoController extends Controller
{
    /**
     * Інформація про API
     *
     * @OA\Get(
     *     path="/info",
     *     operationId="artUaGetInfo",
     *     tags={"Info"},
     *     summary="Інформація про Art-UA Marketplace API",
     *     description="Повертає загальну інформацію про API та його можливості",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Інформація про API",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Art-UA Marketplace API"),
     *             @OA\Property(property="version", type="string", example="1.0.0"),
     *             @OA\Property(property="description", type="string", example="API для маркетплейсу мистецтва art-ua.com"),
     *             @OA\Property(property="status", type="string", example="active")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'name' => 'Art-UA Marketplace API',
            'version' => '1.0.0',
            'description' => 'API для маркетплейсу мистецтва art-ua.com — купівля та продаж творів мистецтва',
            'status' => 'active',
        ]);
    }
}
