<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;

class ProjectController extends Controller
{
    /**
     * Отримати список публічних проєктів з фільтрацією та пагінацією
     *
     * @OA\Get(
     *     path="/v1/projects",
     *     operationId="getProjects",
     *     tags={"Projects"},
     *     summary="Список публічних проектів",
     *     description="Повертає список публічних проектів з можливістю фільтрації та пагінації",
     *
     *     @OA\Parameter(name="art_category", in="query", description="Фільтр по категорії", @OA\Schema(type="string")),
     *     @OA\Parameter(name="art_subcategory", in="query", description="Фільтр по підкатегорії", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Фільтр по статусу", @OA\Schema(type="string", enum={"announced", "in_progress", "completed", "sold"})),
     *     @OA\Parameter(name="budget_min", in="query", description="Мінімальний бюджет", @OA\Schema(type="number")),
     *     @OA\Parameter(name="budget_max", in="query", description="Максимальний бюджет", @OA\Schema(type="number")),
     *     @OA\Parameter(name="search", in="query", description="Пошук по назві", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort_by", in="query", description="Сортування", @OA\Schema(type="string", enum={"announced_at", "budget_goal", "budget_collected", "likes_count", "donors_count"})),
     *     @OA\Parameter(name="sort_dir", in="query", description="Напрямок сортування", @OA\Schema(type="string", enum={"asc", "desc"})),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс 50)", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="page", in="query", description="Номер сторінки", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список проектів",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Project::query()
            ->with('user')
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->orderBy('announced_at', 'desc');

        // Фільтр по категорії
        if ($request->filled('art_category')) {
            $query->where('art_category', $request->input('art_category'));
        }

        // Фільтр по підкатегорії
        if ($request->filled('art_subcategory')) {
            $query->where('art_subcategory', $request->input('art_subcategory'));
        }

        // Фільтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Фільтр по сумі збору (від)
        if ($request->filled('budget_min')) {
            $query->where('budget_goal', '>=', $request->input('budget_min'));
        }

        // Фільтр по сумі збору (до)
        if ($request->filled('budget_max')) {
            $query->where('budget_goal', '<=', $request->input('budget_max'));
        }

        // Пошук по назві
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw("JSON_EXTRACT(title, '$.uk') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_EXTRACT(title, '$.en') LIKE ?", ["%{$search}%"]);
            });
        }

        // Сортування
        $sortBy = $request->input('sort_by', 'announced_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['announced_at', 'budget_goal', 'budget_collected', 'likes_count', 'donors_count'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min($request->input('per_page', 15), 50);
        $projects = $query->paginate($perPage);

        return ProjectListResource::collection($projects);
    }

    /**
     * Отримати деталі проєкту за slug
     *
     * @OA\Get(
     *     path="/v1/projects/{slug}",
     *     operationId="getProject",
     *     tags={"Projects"},
     *     summary="Деталі проекту",
     *     description="Повертає повну інформацію про проект за його slug",
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Slug проекту", @OA\Schema(type="string")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Деталі проекту",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Проект не знайдено")
     * )
     */
    public function show(string $slug): ProjectResource
    {
        $project = Project::query()
            ->with(['user', 'stages' => fn ($q) => $q->orderBy('order'), 'bonuses' => fn ($q) => $q->orderBy('order')])
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->where('slug', $slug)
            ->firstOrFail();

        return new ProjectResource($project);
    }

    /**
     * Отримати список меценатів проєкту
     */
    public function donors(string $slug, Request $request): \Illuminate\Http\JsonResponse
    {
        $project = Project::query()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->where('slug', $slug)
            ->firstOrFail();

        $perPage = min($request->input('per_page', 20), 50);

        $donations = $project->donations()
            ->with('user')
            ->where('status', 'paid')
            ->orderBy('amount', 'desc')
            ->paginate($perPage);

        $donors = $donations->getCollection()->map(function ($donation) {
            return [
                'id' => $donation->id,
                'name' => $donation->getDisplayName(),
                'amount' => (float) $donation->amount,
                'currency' => $donation->currency->value,
                'is_anonymous' => $donation->is_anonymous,
                'donated_at' => $donation->paid_at?->toISOString(),
            ];
        });

        return response()->json([
            'data' => $donors,
            'meta' => [
                'current_page' => $donations->currentPage(),
                'last_page' => $donations->lastPage(),
                'per_page' => $donations->perPage(),
                'total' => $donations->total(),
            ],
        ]);
    }
}
