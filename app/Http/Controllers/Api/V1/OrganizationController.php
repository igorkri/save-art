<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProfileType;
use App\Http\Resources\Api\V1\ArtistResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Organizations",
 *     description="API для роботи з організаціями (публічні профілі)"
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
     *     path="/v1/organizations",
     *     operationId="getOrganizations",
     *     tags={"Organizations"},
     *     summary="Список організацій",
     *     description="Повертає список організацій (користувачів з profile_type=organization, які мають опубліковані проєкти)",
     *
     *     @OA\Parameter(name="search", in="query", description="Пошук по назві організації", @OA\Schema(type="string")),
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
     * Отримати профіль організації
     *
     * @OA\Get(
     *     path="/v1/organizations/{slug}",
     *     operationId="getOrganization",
     *     tags={"Organizations"},
     *     summary="Профіль організації",
     *     description="Повертає публічний профіль організації з її даними та соціальними мережами",
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Унікальний slug організації", @OA\Schema(type="string")),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Профіль організації",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Artist")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Організацію не знайдено",
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
     * Отримати проєкти організації
     *
     * @OA\Get(
     *     path="/v1/organizations/{slug}/projects",
     *     operationId="getOrganizationProjects",
     *     tags={"Organizations"},
     *     summary="Проєкти організації",
     *     description="Повертає список публічних проєктів конкретної організації",
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

    /**
     * Отримати портфоліо-фото організації
     *
     * @OA\Get(
     *     path="/v1/organizations/{slug}/photos",
     *     operationId="getOrganizationPhotos",
     *     tags={"Organizations"},
     *     summary="Портфоліо організації",
     *     description="Повертає список фото з портфоліо організації",
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Slug організації", @OA\Schema(type="string")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список фото",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/UserPhoto"))
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Організацію не знайдено")
     * )
     */
    public function photos(string $slug): AnonymousResourceCollection
    {
        return $this->photosQuery($slug);
    }
}
