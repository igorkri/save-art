<?php

namespace App\Http\Controllers\ArtUA;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Art-UA Marketplace API",
 *     description="API для маркетплейсу мистецтва art-ua.com — купівля та продаж творів мистецтва",
 *     @OA\Contact(
 *         email="support@art-ua.com",
 *         name="Art-UA Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="https://art-ua.com/api/v1",
 *     description="Production Server"
 * )
 *
 * @OA\Server(
 *     url="http://art-ua.local/api/v1",
 *     description="Local Development"
 * )
 *
 * @OA\Tag(
 *     name="Artworks",
 *     description="API для роботи з творами мистецтва"
 * )
 *
 * @OA\Tag(
 *     name="Artists",
 *     description="API для роботи з митцями"
 * )
 *
 * @OA\Tag(
 *     name="Orders",
 *     description="API для роботи з замовленнями"
 * )
 *
 * @OA\Tag(
 *     name="Catalog",
 *     description="API для каталогу"
 * )
 */
class OpenApiInfo
{
    // Цей клас використовується тільки для OpenAPI анотацій
}
