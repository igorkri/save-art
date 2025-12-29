<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    /**
     * Отримати список публічних проєктів з фільтрацією та пагінацією
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
