<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Http\Resources\Api\V1\TeamListResource;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;

/**
 * Публічний список творчих команд art-ua-info. На відміну від митців/організацій
 * команда не прив'язана до art_category напряму (лише через проєкти учасників),
 * тому фільтр по галузі мистецтва тут не застосовується.
 *
 * @OA\Tag(
 *     name="Teams",
 *     description="API для роботи з творчими командами (публічні профілі, art-ua-info)"
 * )
 */
class TeamController extends Controller
{
    /**
     * Отримати список команд
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/teams",
     *     operationId="artUaInfoGetTeams",
     *     tags={"Teams"},
     *     summary="Список команд",
     *     description="Повертає список творчих команд",
     *
     *     @OA\Parameter(name="search", in="query", description="Пошук по назві команди", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс. 50)", @OA\Schema(type="integer", default=20, maximum=50)),
     *     @OA\Parameter(name="page", in="query", description="Номер сторінки", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список команд",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TeamListItem")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Team::query()->with('members')->withCount('members');

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.uk'))) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ["%{$search}%"]);
            });
        }

        $perPage = min($request->input('per_page', 20), 50);
        $teams = $query->paginate($perPage);

        return TeamListResource::collection($teams);
    }

    /**
     * Отримати профіль команди
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/teams/{slug}",
     *     operationId="artUaInfoGetTeam",
     *     tags={"Teams"},
     *     summary="Профіль команди",
     *     description="Повертає публічний профіль команди",
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Slug команди", @OA\Schema(type="string")),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(response=200, description="Профіль команди", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/TeamListItem"))),
     *     @OA\Response(response=404, description="Команду не знайдено", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(string $slug): TeamListResource
    {
        $team = Team::query()->with('members')->withCount('members')->where('slug', $slug)->firstOrFail();

        return new TeamListResource($team);
    }

    /**
     * Отримати опубліковані проєкти учасників команди
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/teams/{slug}/projects",
     *     operationId="artUaInfoGetTeamProjects",
     *     tags={"Teams"},
     *     summary="Проєкти команди",
     *     description="Повертає опубліковані art-ua-info проєкти, власником яких є сама команда (team_id)",
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Slug команди", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс. 50)", @OA\Schema(type="integer", default=15, maximum=50)),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список проєктів команди",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProjectList")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Команду не знайдено")
     * )
     */
    public function projects(string $slug, Request $request): AnonymousResourceCollection
    {
        $team = Team::query()->where('slug', $slug)->firstOrFail();

        // Лише проєкти, підписані власне на команду (team_id) — не особисті проєкти
        // учасників, опубліковані від свого імені.
        $query = Project::query()
            ->forArtUaInfo()
            ->with(['user.profileLegal', 'team'])
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->where('team_id', $team->id)
            ->orderBy('announced_at', 'desc');

        $perPage = min($request->input('per_page', 15), 50);
        $projects = $query->paginate($perPage);

        return ProjectListResource::collection($projects);
    }
}
