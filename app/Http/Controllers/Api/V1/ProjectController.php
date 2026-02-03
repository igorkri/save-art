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
     *     description="Повертає список публічних проектів з можливістю фільтрації та пагінації. Параметри art_category, art_subcategory та status підтримують множинні значення через кому.",
     *
     *     @OA\Parameter(name="art_category", in="query", description="Фільтр по категорії (можна вказати кілька через кому)", @OA\Schema(type="string"), example="visual,literary"),
     *     @OA\Parameter(name="art_subcategory", in="query", description="Фільтр по підкатегорії (можна вказати кілька через кому)", @OA\Schema(type="string"), example="poetry,prose,graphics,painting"),
     *     @OA\Parameter(name="status", in="query", description="Фільтр по статусу (можна вказати кілька через кому)", @OA\Schema(type="string"), example="announced,in_progress,completed"),
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

        // Фільтр по категорії (підтримує множинні значення через кому)
        if ($request->filled('art_category')) {
            $categories = array_map('trim', explode(',', $request->input('art_category')));
            $query->whereIn('art_category', $categories);
        }

        // Фільтр по підкатегорії (підтримує множинні значення через кому)
        if ($request->filled('art_subcategory')) {
            $subcategories = array_map('trim', explode(',', $request->input('art_subcategory')));
            $query->whereIn('art_subcategory', $subcategories);
        }

        // Фільтр по статусу (підтримує множинні значення через кому)
        if ($request->filled('status')) {
            $statuses = array_map('trim', explode(',', $request->input('status')));
            // Перевіряємо, що статуси є публічними
            $publicStatuses = array_map(fn ($s) => $s->value, ProjectStatus::publicStatuses());
            $validStatuses = array_intersect($statuses, $publicStatuses);
            if (! empty($validStatuses)) {
                $query->whereIn('status', $validStatuses);
            }
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
     *     description="Повертає повну інформацію про проект за його slug, включаючи етапи та бонуси",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Унікальний slug проекту",
     *
     *         @OA\Schema(type="string"),
     *         example="miy-noviy-proekt-abc123"
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Деталі проекту",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Project")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Проект не знайдено",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
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
     *
     * @OA\Get(
     *     path="/v1/projects/{slug}/donors",
     *     operationId="getProjectDonors",
     *     tags={"Projects"},
     *     summary="Список меценатів проекту",
     *     description="Повертає список користувачів, які зробили донати на проект (відсортовано за сумою)",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug проекту",
     *
     *         @OA\Schema(type="string"),
     *         example="miy-noviy-proekt-abc123"
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
     *     @OA\Response(
     *         response=200,
     *         description="Список меценатів",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(type="object",
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Іван П.", description="Ім'я або 'Анонім'"),
     *                     @OA\Property(property="amount", type="number", format="float", example=1000.00),
     *                     @OA\Property(property="currency", type="string", example="UAH"),
     *                     @OA\Property(property="is_anonymous", type="boolean", example=false),
     *                     @OA\Property(property="donated_at", type="string", format="date-time", example="2025-01-05T15:30:00.000Z")
     *                 )
     *             ),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Проект не знайдено")
     * )
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
