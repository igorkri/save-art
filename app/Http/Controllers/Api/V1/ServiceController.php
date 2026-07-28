<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\ArtCategory;
use App\Models\Service;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Services",
 *     description="Послуги митців, організацій та команд"
 * )
 */
class ServiceController extends Controller
{
    /**
     * Отримати список послуг
     *
     * @OA\Get(
     *     path="/v1/services",
     *     operationId="getServices",
     *     tags={"Services"},
     *     summary="Список послуг",
     *     description="Повертає список послуг з можливістю фільтрації за категорією мистецтва та типом виконавця",
     *
     *     @OA\Parameter(name="art_category", in="query", description="Slug кореневої категорії мистецтва", @OA\Schema(type="string")),
     *     @OA\Parameter(name="art_subcategory", in="query", description="Slug підкатегорії мистецтва", @OA\Schema(type="string")),
     *     @OA\Parameter(name="performer_type", in="query", description="Тип виконавця", @OA\Schema(type="string", enum={"artist", "team"})),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс. 50)", @OA\Schema(type="integer", default=15, maximum=50)),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список послуг",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Service")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Service::query()->with(['serviceable', 'artCategory']);

        if ($request->filled('art_category') || $request->filled('art_subcategory')) {
            $categoryId = ArtCategory::resolveIdFromSlugs(
                $request->input('art_category'),
                $request->input('art_subcategory')
            );

            $query->when($categoryId, fn ($q) => $q->where('art_category_id', $categoryId));
        }

        if ($request->input('performer_type') === 'team') {
            $query->where('serviceable_type', Team::class);
        } elseif ($request->input('performer_type') === 'artist') {
            $query->where('serviceable_type', User::class);
        }

        $perPage = min($request->input('per_page', 15), 50);
        $services = $query->paginate($perPage);

        return ServiceResource::collection($services);
    }

    /**
     * Отримати послугу
     *
     * @OA\Get(
     *     path="/v1/services/{slug}",
     *     operationId="getService",
     *     tags={"Services"},
     *     summary="Послуга за slug",
     *     description="Повертає одну послугу з даними виконавця",
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Slug послуги", @OA\Schema(type="string")),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Послуга",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Service")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Послугу не знайдено")
     * )
     */
    public function show(string $slug): ServiceResource
    {
        $service = Service::query()
            ->with(['serviceable', 'artCategory'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ServiceResource($service);
    }
}
