<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
     *     description="Повертає проєкти з реальними оплаченими донатами (агреговано з таблиці donations), з можливістю фільтрації та пагінації",
     *     security={{"apiKey": {}}},
     *
     *     @OA\Parameter(name="project_id", in="query", description="Фільтр по ID проекту", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="user_id", in="query", description="Фільтр по ID користувача", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", description="Пошук по назві", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort", in="query", description="Сортування: newest (за замовчуванням), most_funded", @OA\Schema(type="string", enum={"newest", "most_funded"})),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс 50)", @OA\Schema(type="integer", default=12)),
     *     @OA\Parameter(name="page", in="query", description="Номер сторінки", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
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
        $language = $this->getLanguage($request) ?? 'uk';

        $donatedFilter = fn ($q) => $q->where('status', 'paid')->where('donation_type', 'project');

        $query = Project::query()
            ->select('id', 'title', 'slug', 'cover', 'budget_goal', 'user_id', 'status', 'completed_at', 'created_at')
            ->with('user:id,full_name,slug,avatar')
            ->withSum(['donations as collected_amount' => $donatedFilter], 'amount')
            ->withMax(['donations as last_donation_at' => $donatedFilter], 'paid_at')
            ->whereHas('donations', $donatedFilter);

        // Фільтр по проєкту
        if ($request->filled('project_id')) {
            $query->where('id', $request->input('project_id'));
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

        match ($request->input('sort')) {
            'most_funded' => $query->orderByDesc('collected_amount'),
            default => $query->orderByDesc('last_donation_at'),
        };

        $perPage = min($request->input('per_page', 12), 50);
        $projects = $query->paginate($perPage);

        $reports = collect($projects->items())->map(fn (Project $project) => $this->formatDonatedProject($project, $language));
        $total = $projects->total();

        // Донати на платформу без прив'язки до проєкту показуємо окремим рядком,
        // щоб сума списку збігалась із загальною сумою на графіку
        $showPlatformRow = $projects->currentPage() === 1
            && ! $request->filled('project_id')
            && ! $request->filled('search');

        if ($showPlatformRow) {
            $platformDonations = \App\Models\Donation::query()
                ->where('status', 'paid')
                ->where('donation_type', 'platform');

            if ($request->filled('user_id')) {
                $platformDonations->where('user_id', $request->input('user_id'));
            }

            $platformTotal = (clone $platformDonations)->sum('amount');

            if ($platformTotal > 0) {
                $lastDonationAt = (clone $platformDonations)->max('paid_at');
                $reports->prepend($this->formatPlatformDonations($language, (float) $platformTotal, $lastDonationAt));
                $total++;
            }
        }

        return response()->json([
            'result' => true,
            'data' => [
                'reports' => $reports->values(),
                'pagination' => [
                    'current_page' => $projects->currentPage(),
                    'per_page' => $projects->perPage(),
                    'total' => $total,
                    'total_pages' => $projects->lastPage(),
                    'has_next' => $projects->hasMorePages(),
                    'has_prev' => $projects->currentPage() > 1,
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
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
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
    public function show(Request $request, int $id): JsonResponse
    {
        $language = $this->getLanguage($request);

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
                'report' => $this->formatReport($report, $language),
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
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
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
        $language = $this->getLanguage($request);

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
                    'title' => $this->localizeValue($project->title, $language),
                    'slug' => $project->slug,
                ],
                'reports' => collect($reports->items())->map(fn (Report $report) => $this->formatReport($report, $language)),
                'pagination' => [
                    'current_page' => $reports->currentPage(),
                    'per_page' => $reports->perPage(),
                    'total' => $reports->total(),
                    'total_pages' => $reports->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Форматувати проєкт із агрегованими реальними донатами для публічного списку /v1/reports
     *
     * @return array<string, mixed>
     */
    private function formatDonatedProject(Project $project, ?string $language): array
    {
        $status = match ($project->status) {
            ProjectStatus::Completed, ProjectStatus::Sold => 'completed',
            ProjectStatus::InProgress, ProjectStatus::Paused => 'in_progress',
            default => 'announced',
        };

        return [
            'id' => $project->id,
            'title' => $this->localizeValue($project->title, $language),
            'cover' => $project->cover,
            'collected_amount' => (float) $project->collected_amount,
            'goal_amount' => (float) $project->budget_goal,
            'spent_amount' => 0,
            'report_date' => $project->last_donation_at ? \Illuminate\Support\Carbon::parse($project->last_donation_at)->toDateString() : null,
            'status' => $status,
            'completed_at' => $status === 'completed' ? $project->completed_at?->toDateString() : null,
            'created_at' => $project->created_at->toISOString(),
            'project' => [
                'id' => $project->id,
                'title' => $this->localizeValue($project->title, $language),
                'slug' => $project->slug,
                'cover' => $project->cover,
            ],
            'user' => $project->user ? [
                'id' => $project->user->id,
                'name' => $project->user->name,
                'slug' => $project->user->slug,
                'avatar_url' => $project->user->avatar ? Storage::url($project->user->avatar) : null,
            ] : null,
        ];
    }

    /**
     * Сформувати рядок для донатів на платформу (без прив'язки до проєкту)
     *
     * @return array<string, mixed>
     */
    private function formatPlatformDonations(?string $language, float $total, ?string $lastDonationAt): array
    {
        $title = ['uk' => 'Донати платформі', 'en' => 'Platform donations'];

        return [
            'id' => 'platform',
            'title' => $this->localizeValue($title, $language),
            'cover' => null,
            'collected_amount' => $total,
            'goal_amount' => $total,
            'spent_amount' => 0,
            'report_date' => $lastDonationAt ? \Illuminate\Support\Carbon::parse($lastDonationAt)->toDateString() : null,
            'status' => 'in_progress',
            'completed_at' => null,
            'created_at' => now()->toISOString(),
            'project' => null,
            'user' => null,
        ];
    }

    /**
     * Форматувати звіт з локалізацією
     *
     * @return array<string, mixed>
     */
    private function formatReport(Report $report, ?string $language): array
    {
        $data = [
            'id' => $report->id,
            'title' => $this->localizeValue($report->title, $language),
            'description' => $this->localizeValue($report->description, $language),
            'cover' => $report->cover,
            'images' => $report->images,
            'attachments' => $report->attachments,
            'collected_amount' => (float) $report->collected_amount,
            'goal_amount' => (float) $report->goal_amount,
            'spent_amount' => (float) $report->spent_amount,
            'report_date' => $report->report_date?->toDateString(),
            'status' => $report->status,
            'created_at' => $report->created_at->toISOString(),
        ];

        if ($report->relationLoaded('project') && $report->project) {
            $data['project'] = [
                'id' => $report->project->id,
                'title' => $this->localizeValue($report->project->title, $language),
                'slug' => $report->project->slug,
                'cover' => $report->project->cover,
            ];
        }

        if ($report->relationLoaded('user') && $report->user) {
            $data['user'] = [
                'id' => $report->user->id,
                'name' => $report->user->name,
            ];
        }

        return $data;
    }

    /**
     * Отримати мову з запиту
     */
    private function getLanguage(Request $request): ?string
    {
        $language = $request->query('language');

        return ($language && in_array($language, ['uk', 'en'])) ? $language : null;
    }

    /**
     * Локалізувати значення поля
     */
    private function localizeValue(mixed $value, ?string $language): mixed
    {
        if ($language === null || ! is_array($value)) {
            return $value;
        }

        if (isset($value['uk']) || isset($value['en'])) {
            return $value[$language] ?? $value['uk'] ?? reset($value);
        }

        return $value;
    }
}
