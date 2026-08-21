<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Enums\ProfileType;
use App\Http\Resources\Api\V1\ArtistResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Organizations",
 *     description="API для роботи з організаціями (публічні профілі, art-ua-info)"
 * )
 */
class OrganizationController extends AuthorController
{
    protected function profileType(): ?ProfileType
    {
        return ProfileType::Organization;
    }

    /**
     * Отримати список організацій
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/organizations",
     *     operationId="artUaInfoGetOrganizations",
     *     tags={"Organizations"},
     *     summary="Список організацій",
     *     description="Повертає список організацій (користувачів з profile_type=organization, які мають опубліковані art-ua-info проєкти) з фільтрами по галузі мистецтва",
     *
     *     @OA\Parameter(name="search", in="query", description="Пошук по назві організації", @OA\Schema(type="string")),
     *     @OA\Parameter(name="art_category", in="query", description="Фільтр по категорії", @OA\Schema(type="string")),
     *     @OA\Parameter(name="art_subcategory", in="query", description="Фільтр по підкатегорії (можна вказати кілька через кому)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort_by", in="query", @OA\Schema(type="string", enum={"projects_count", "name"})),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс. 50)", @OA\Schema(type="integer", default=20, maximum=50)),
     *     @OA\Parameter(name="page", in="query", description="Номер сторінки", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список організацій",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Artist")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="filters", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return $this->indexQuery($request);
    }

    /**
     * Отримати профіль організації
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/organizations/{slug}",
     *     operationId="artUaInfoGetOrganization",
     *     tags={"Organizations"},
     *     summary="Профіль організації",
     *     description="Повертає публічний профіль організації з її даними та соціальними мережами",
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Унікальний slug організації", @OA\Schema(type="string")),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(response=200, description="Профіль організації", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Artist"))),
     *     @OA\Response(response=404, description="Організацію не знайдено", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(string $slug): ArtistResource
    {
        return $this->showQuery($slug);
    }

    /**
     * Отримати проєкти організації
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/organizations/{slug}/projects",
     *     operationId="artUaInfoGetOrganizationProjects",
     *     tags={"Organizations"},
     *     summary="Проєкти організації",
     *     description="Повертає список публічних art-ua-info проєктів конкретної організації",
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Slug організації", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Фільтр по статусу проєкту", @OA\Schema(type="string", enum={"announced", "in_progress", "completed", "sold"})),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс. 50)", @OA\Schema(type="integer", default=15, maximum=50)),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список проєктів організації",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProjectList")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Організацію не знайдено")
     * )
     */
    public function projects(string $slug, Request $request): AnonymousResourceCollection
    {
        return $this->projectsQuery($slug, $request);
    }
}
