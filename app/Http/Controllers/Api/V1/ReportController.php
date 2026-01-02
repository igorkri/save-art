<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Отримати список публічних звітів з пагінацією
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
