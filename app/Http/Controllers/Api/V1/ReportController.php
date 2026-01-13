<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ReportController extends Controller
{
    /**
     * Отримати список публічних звітів з пагінацією
     *
     * @OA\Get(
     *     path="/v1/reports",
     *     operationId="getReports",
     *     tags={"Reports"},
     *     summary="Список звітів",
     *     description="Повертає публічні звіти з можливістю фільтрації та пагінації",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="project_id", in="query", description="Фільтр по ID проекту", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="user_id", in="query", description="Фільтр по ID користувача", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", description="Пошук по назві", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс 50)", @OA\Schema(type="integer", default=12)),
     *     @OA\Parameter(name="page", in="query", description="Номер сторінки", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список звітів",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="reports", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="pagination", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Report::query()
            ->with(['project:id,title,slug,cover', 'user:id,name'])
            ->published()
            ->orderBy('report_date', 'desc');

        // Фільтр по проєкту
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        // Фільтр по користувачу
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Пошук
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.uk')) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) LIKE ?", ["%{$search}%"]);
            });
        }

        $perPage = min($request->input('per_page', 12), 50);
        $reports = $query->paginate($perPage);

        return response()->json([
            'result' => true,
            'data' => [
                'reports' => $reports->items(),
                'pagination' => [
                    'current_page' => $reports->currentPage(),
                    'per_page' => $reports->perPage(),
                    'total' => $reports->total(),
                    'total_pages' => $reports->lastPage(),
                    'has_next' => $reports->hasMorePages(),
                    'has_prev' => $reports->currentPage() > 1,
                ],
            ],
        ]);
    }

    /**
     * Отримати деталі конкретного звіту
     *
     * @OA\Get(
     *     path="/v1/reports/{id}",
     *     operationId="getReport",
     *     tags={"Reports"},
     *     summary="Деталі звіту",
     *     description="Повертає детальну інформацію про конкретний звіт",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID звіту", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Дані звіту",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="report", type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Звіт не знайдено")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $report = Report::query()
            ->with([
                'project' => fn ($q) => $q->select('id', 'title', 'slug', 'cover', 'status', 'budget_goal', 'budget_collected'),
                'user:id,name',
            ])
            ->published()
            ->findOrFail($id);

        return response()->json([
            'result' => true,
            'data' => [
                'report' => $report,
            ],
        ]);
    }

    /**
     * Отримати звіти конкретного проєкту
     *
     * @OA\Get(
     *     path="/v1/projects/{slug}/reports",
     *     operationId="getProjectReports",
     *     tags={"Reports"},
     *     summary="Звіти проекту",
     *     description="Повертає всі публічні звіти для конкретного проекту",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Slug проекту", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс 50)", @OA\Schema(type="integer", default=10)),
     *     @OA\Parameter(name="page", in="query", description="Номер сторінки", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Звіти проекту",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="project", type="object"),
     *                 @OA\Property(property="reports", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="pagination", type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Проект не знайдено")
     * )
     */
    public function byProject(string $slug, Request $request): JsonResponse
    {
        $project = \App\Models\Project::query()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->where('slug', $slug)
            ->firstOrFail();

        $perPage = min($request->input('per_page', 10), 50);

        $reports = Report::query()
            ->with('user:id,name')
            ->where('project_id', $project->id)
            ->published()
            ->orderBy('report_date', 'desc')
            ->paginate($perPage);

        return response()->json([
            'result' => true,
            'data' => [
                'project' => [
                    'id' => $project->id,
                    'title' => $project->title,
                    'slug' => $project->slug,
                ],
                'reports' => $reports->items(),
                'pagination' => [
                    'current_page' => $reports->currentPage(),
                    'per_page' => $reports->perPage(),
                    'total' => $reports->total(),
                    'total_pages' => $reports->lastPage(),
                ],
            ],
        ]);
    }
}
