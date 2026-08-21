<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProfileType;
use App\Http\Resources\Api\V1\ArtistResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Artists",
 *     description="API для роботи з митцями (публічні профілі)"
 * )
 */
class ArtistController extends AuthorController
{
    protected function profileType(): ?ProfileType
    {
        return null;
    }

    /**
     * Отримати список митців (користувачів з проєктами)
     *
     * @OA\Get(
     *     path="/v1/artists",
     *     operationId="getArtists",
     *     tags={"Artists"},
     *     summary="Список митців",
     *     description="Повертає список митців (користувачів, які мають опубліковані проєкти)",
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Пошук по імені митця",
     *
     *         @OA\Schema(type="string"),
     *         example="Іван"
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Кількість на сторінку (макс. 50)",
     *
     *         @OA\Schema(type="integer", default=20, maximum=50),
     *         example=20
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Номер сторінки",
     *
     *         @OA\Schema(type="integer", default=1),
     *         example=1
     *     ),
     *
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список митців",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Artist")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return $this->indexQuery($request);
    }

    /**
     * Отримати профіль митця
     *
     * @OA\Get(
     *     path="/v1/artists/{slug}",
     *     operationId="getArtist",
     *     tags={"Artists"},
     *     summary="Профіль митця",
     *     description="Повертає публічний профіль митця з його даними та соціальними мережами",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Унікальний slug митця",
     *
     *         @OA\Schema(type="string"),
     *         example="ivan-franko-abc123"
     *     ),
     *
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Профіль митця",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Artist")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Митця не знайдено",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(string $slug): ArtistResource
    {
        return $this->showQuery($slug);
    }

    /**
     * Отримати проєкти митця
     *
     * @OA\Get(
     *     path="/v1/artists/{slug}/projects",
     *     operationId="getArtistProjects",
     *     tags={"Artists"},
     *     summary="Проєкти митця",
     *     description="Повертає список публічних проєктів конкретного митця",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug митця",
     *
     *         @OA\Schema(type="string"),
     *         example="ivan-franko-abc123"
     *     ),
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Фільтр по статусу проєкту",
     *
     *         @OA\Schema(type="string", enum={"announced", "in_progress", "completed", "sold"}),
     *         example="announced"
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Кількість на сторінку (макс. 50)",
     *
     *         @OA\Schema(type="integer", default=15, maximum=50)
     *     ),
     *
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список проєктів митця",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProjectList")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Митця не знайдено")
     * )
     */
    public function projects(string $slug, Request $request): AnonymousResourceCollection
    {
        return $this->projectsQuery($slug, $request);
    }
}
