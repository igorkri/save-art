<?php

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="SaveArt API",
 *     description="API для платформи save-art.in.ua — краудфандингова платформа для митців та меценатів",
 *     @OA\Contact(
 *         email="support@save-art.in.ua",
 *         name="Save-Art Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="https://idart.dev2025.ingsot.com/api",
 *     description="Production Server (temporary)"
 * )
 *
 * @OA\Server(
 *     url="https://save-art.in.ua/api",
 *     description="Production Server (main)"
 * )
 *
 * @OA\Server(
 *     url="http://save-art.local/api",
 *     description="Local Development"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum Token"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="apiKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-Api-Key",
 *     description="API ключ для доступу до API"
 * )
 *
 * @OA\Schema(
 *     schema="LocalizedString",
 *     title="LocalizedString",
 *     description="Мультимовний текст (uk/en)",
 *     type="object",
 *     @OA\Property(property="uk", type="string", example="Український текст"),
 *     @OA\Property(property="en", type="string", nullable=true, example="English text")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     title="PaginationMeta",
 *     type="object",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=10),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="to", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=150)
 * )
 *
 * @OA\Schema(
 *     schema="PaginationLinks",
 *     title="PaginationLinks",
 *     type="object",
 *     @OA\Property(property="first", type="string"),
 *     @OA\Property(property="last", type="string"),
 *     @OA\Property(property="prev", type="string", nullable=true),
 *     @OA\Property(property="next", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     title="ValidationError",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Валідацію не пройдено."),
 *     @OA\Property(property="errors", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     title="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Ресурс не знайдено."),
 *     @OA\Property(property="error", type="string", example="Not Found")
 * )
 *
 * @OA\Schema(
 *     schema="Author",
 *     title="Author",
 *     description="Автор проєкту",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Іван Франко"),
 *     @OA\Property(property="slug", type="string", nullable=true, example="ivan-franko"),
 *     @OA\Property(property="avatar_url", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="Statistics",
 *     title="Statistics",
 *     description="Статистика платформи",
 *     type="object",
 *     @OA\Property(property="total_projects", type="integer", example=150),
 *     @OA\Property(property="active_projects", type="integer", example=45),
 *     @OA\Property(property="completed_projects", type="integer", example=80),
 *     @OA\Property(property="total_artists", type="integer", example=120),
 *     @OA\Property(property="total_donations", type="number", format="float", example=1500000.00),
 *     @OA\Property(property="total_donors", type="integer", example=3500)
 * )
 */
class OpenApiInfo
{
    // Цей клас використовується тільки для OpenAPI анотацій
}
