<?php

namespace App\Http\Controllers\ArtInfo;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Art-UA Info API",
 *     description="API для інформаційного порталу art-ua.info — новини, події, статті про мистецтво",
 *
 *     @OA\Contact(
 *         email="info@art-ua.info",
 *         name="Art-UA Info Support"
 *     ),
 *
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="https://art-ua.info/api/v1",
 *     description="Production Server"
 * )
 * @OA\Server(
 *     url="https://save-art.ddev.site/api/v1",
 *     description="Local Development"
 * )
 *
 * @OA\Tag(
 *     name="Articles",
 *     description="API для роботи зі статтями"
 * )
 * @OA\Tag(
 *     name="Events",
 *     description="API для роботи з подіями"
 * )
 * @OA\Tag(
 *     name="News",
 *     description="API для роботи з новинами"
 * )
 *
 * @OA\Schema(
 *     schema="LocalizedString",
 *     title="LocalizedString",
 *     description="Мультимовний текст (uk/en)",
 *     type="object",
 *
 *     @OA\Property(property="uk", type="string"),
 *     @OA\Property(property="en", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     title="PaginationMeta",
 *     type="object",
 *
 *     @OA\Property(property="current_page", type="integer"),
 *     @OA\Property(property="last_page", type="integer"),
 *     @OA\Property(property="per_page", type="integer"),
 *     @OA\Property(property="total", type="integer")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationLinks",
 *     title="PaginationLinks",
 *     type="object",
 *
 *     @OA\Property(property="first", type="string", nullable=true),
 *     @OA\Property(property="last", type="string", nullable=true),
 *     @OA\Property(property="prev", type="string", nullable=true),
 *     @OA\Property(property="next", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     title="ErrorResponse",
 *     type="object",
 *
 *     @OA\Property(property="message", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="Author",
 *     title="Author",
 *     description="Автор проєкту",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Іван Франко"),
 *     @OA\Property(property="slug", type="string", nullable=true, example="ivan-franko"),
 *     @OA\Property(property="avatar_url", type="string", nullable=true)
 * )
 */
class OpenApiInfo
{
    // Цей клас використовується тільки для OpenAPI анотацій
}
