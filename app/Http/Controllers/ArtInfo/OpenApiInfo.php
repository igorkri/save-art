<?php

namespace App\Http\Controllers\ArtInfo;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Art-UA Info API",
 *     description="API для інформаційного порталу art-ua.info — новини, події, статті про мистецтво",
 *     @OA\Contact(
 *         email="info@art-ua.info",
 *         name="Art-UA Info Support"
 *     ),
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
 *
 * @OA\Server(
 *     url="http://art-ua.info.local/api/v1",
 *     description="Local Development"
 * )
 *
 * @OA\Tag(
 *     name="Articles",
 *     description="API для роботи зі статтями"
 * )
 *
 * @OA\Tag(
 *     name="Events",
 *     description="API для роботи з подіями"
 * )
 *
 * @OA\Tag(
 *     name="News",
 *     description="API для роботи з новинами"
 * )
 */
class OpenApiInfo
{
    // Цей клас використовується тільки для OpenAPI анотацій
}
