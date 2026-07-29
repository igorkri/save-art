<?php

namespace App\Http\Controllers\ArtInfo;

use OpenApi\Annotations as OA;

/**
 * Документує GET /v1/my/projects/completed (реалізовано в
 * App\Http\Controllers\Api\V1\MyProjectController::completed) для схеми Art-UA Info API.
 * Клас використовується тільки для OpenAPI анотацій, роут не реєструє.
 *
 * @OA\Tag(
 *     name="My Projects",
 *     description="API для управління власними проєктами (кабінет митця)"
 * )
 *
 * @OA\Get(
 *     path="/v1/my/projects/completed",
 *     operationId="getMyCompletedProjectsArtInfo",
 *     tags={"My Projects"},
 *     summary="Список моїх завершених проєктів (save-art + art-ua-info)",
 *     description="Повертає завершені проєкти (completed, sold) авторизованого користувача незалежно від джерела створення (save-art або art-ua-info).",
 *     security={{"sanctum":{}, "apiKey":{}}},
 *
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15, maximum=50)),
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
 *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Список завершених проєктів",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProjectList")),
 *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
 *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Не авторизовано")
 * )
 */
class MyProjectsCompletedDocs
{
    // Цей клас використовується тільки для OpenAPI анотацій
}
