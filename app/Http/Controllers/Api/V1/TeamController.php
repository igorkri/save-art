<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Http\Resources\Api\V1\TeamResource;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Teams",
 *     description="API для роботи з творчими командами"
 * )
 */
class TeamController extends Controller
{
    /**
     * Отримати список команд
     *
     * @OA\Get(
     *     path="/v1/teams",
     *     operationId="getTeams",
     *     tags={"Teams"},
     *     summary="Список команд",
     *     description="Повертає список творчих команд",
     *
     *     @OA\Parameter(name="search", in="query", description="Пошук по назві команди", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс. 50)", @OA\Schema(type="integer", default=20, maximum=50)),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список команд",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Team")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Team::query()->with('members');

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.uk'))) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ["%{$search}%"]);
            });
        }

        $perPage = min($request->input('per_page', 20), 50);
        $teams = $query->paginate($perPage);

        return TeamResource::collection($teams);
    }

    /**
     * Отримати профіль команди
     *
     * @OA\Get(
     *     path="/v1/teams/{slug}",
     *     operationId="getTeam",
     *     tags={"Teams"},
     *     summary="Профіль команди",
     *     description="Повертає публічний профіль команди з учасниками",
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Slug команди", @OA\Schema(type="string")),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Профіль команди",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Team")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Команду не знайдено")
     * )
     */
    public function show(string $slug): TeamResource
    {
        $team = Team::query()
            ->with('members')
            ->where('slug', $slug)
            ->firstOrFail();

        return new TeamResource($team);
    }

    /**
     * Отримати послуги команди
     *
     * @OA\Get(
     *     path="/v1/teams/{slug}/services",
     *     operationId="getTeamServices",
     *     tags={"Teams"},
     *     summary="Послуги команди",
     *     description="Повертає список послуг, які пропонує команда",
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Slug команди", @OA\Schema(type="string")),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список послуг",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Service"))
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Команду не знайдено")
     * )
     */
    public function services(string $slug): AnonymousResourceCollection
    {
        $team = Team::query()->where('slug', $slug)->firstOrFail();

        return ServiceResource::collection($team->services()->with('artCategory')->get());
    }
}
