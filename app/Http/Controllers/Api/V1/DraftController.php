<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

class DraftController extends Controller
{
    /**
     * Отримати список чернеток користувача
     *
     * @OA\Get(
     *     path="/v1/my/drafts",
     *     operationId="getDrafts",
     *     tags={"Drafts"},
     *     summary="Список чернеток користувача",
     *     description="Повертає список всіх чернеток (проектів зі статусом Draft) поточного користувача",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список чернеток",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="drafts", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="count", type="integer", example=5)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $drafts = Project::query()
            ->where('user_id', $user->id)
            ->where('status', ProjectStatus::Draft)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn (Project $project) => $this->formatDraft($project));

        return response()->json([
            'result' => true,
            'data' => [
                'drafts' => $drafts,
                'count' => $drafts->count(),
            ],
        ]);
    }

    /**
     * Зберегти або оновити чернетку
     *
     * @OA\Post(
     *     path="/v1/my/drafts",
     *     operationId="storeDraft",
     *     tags={"Drafts"},
     *     summary="Створити або оновити чернетку",
     *     description="Створює нову чернетку або оновлює існуючу за local_id",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="local_id", type="string", description="Локальний ID для синхронізації"),
     *             @OA\Property(property="title", type="object",
     *                 @OA\Property(property="uk", type="string"),
     *                 @OA\Property(property="en", type="string")
     *             ),
     *             @OA\Property(property="short_description", type="object"),
     *             @OA\Property(property="cover", type="string"),
     *             @OA\Property(property="art_category", type="string"),
     *             @OA\Property(property="art_subcategory", type="string"),
     *             @OA\Property(property="budget_goal", type="number"),
     *             @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}),
     *             @OA\Property(property="budget_items", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="characteristics", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="stages", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="bonuses", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Чернетку створено/оновлено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'local_id' => 'nullable|string|max:100',
            'title' => 'nullable|array',
            'title.uk' => 'nullable|string|max:255',
            'title.en' => 'nullable|string|max:255',
            'short_description' => 'nullable|array',
            'short_description.uk' => 'nullable|string',
            'short_description.en' => 'nullable|string',
            'cover' => 'nullable|string',
            'art_category' => 'nullable|string',
            'art_subcategory' => 'nullable|string',
            'budget_goal' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|in:UAH,USD,EUR',
            'budget_items' => 'nullable|array',
            'characteristics' => 'nullable|array',
            'additional_info' => 'nullable|array',
            'stages' => 'nullable|array',
            'bonuses' => 'nullable|array',
        ]);

        // Якщо передано local_id — перевіряємо, чи є вже такий проєкт
        $project = null;
        if ($request->filled('local_id')) {
            $project = Project::query()
                ->where('user_id', $user->id)
                ->where('status', ProjectStatus::Draft)
                ->where('additional_info->local_id', $request->input('local_id'))
                ->first();
        }

        if (! $project) {
            // Створюємо новий проєкт-чернетку
            $project = new Project;
            $project->user_id = $user->id;
            $project->status = ProjectStatus::Draft;
            $project->code = Project::generateUniqueCode();

            // Генеруємо slug з назви або випадковий
            $title = $validated['title'] ?? ['uk' => 'Чернетка '.now()->format('d.m.Y H:i')];
            $project->slug = Project::generateSlugFromTitle($title);
            // Встановлюємо дефолтний title для нових чернеток
            $project->title = $title;
        }

        // Оновлюємо дані
        if (isset($validated['title'])) {
            $project->title = $validated['title'];
        }
        if (isset($validated['short_description'])) {
            $project->short_description = $validated['short_description'];
        }
        if (isset($validated['cover'])) {
            $project->cover = $validated['cover'];
        }
        if (isset($validated['art_category'])) {
            $project->art_category = $validated['art_category'];
        }
        if (isset($validated['art_subcategory'])) {
            $project->art_subcategory = $validated['art_subcategory'];
        }
        if (isset($validated['budget_goal'])) {
            $project->budget_goal = $validated['budget_goal'];
        }
        if (isset($validated['currency'])) {
            $project->currency = $validated['currency'];
        }
        if (isset($validated['budget_items'])) {
            $project->budget_items = $validated['budget_items'];
        }
        if (isset($validated['characteristics'])) {
            $project->characteristics = $validated['characteristics'];
        }

        // Зберігаємо local_id та інші дані в additional_info
        $additionalInfo = $project->additional_info ?? [];
        if ($request->filled('local_id')) {
            $additionalInfo['local_id'] = $request->input('local_id');
        }
        if (isset($validated['additional_info'])) {
            $additionalInfo = array_merge($additionalInfo, $validated['additional_info']);
        }
        $project->additional_info = $additionalInfo;

        $project->save();

        // Зберігаємо етапи, якщо передані
        if (isset($validated['stages']) && is_array($validated['stages'])) {
            $this->syncStages($project, $validated['stages']);
        }

        // Зберігаємо бонуси, якщо передані
        if (isset($validated['bonuses']) && is_array($validated['bonuses'])) {
            $this->syncBonuses($project, $validated['bonuses']);
        }

        return response()->json([
            'result' => true,
            'message' => 'Чернетку збережено',
            'data' => [
                'draft' => $this->formatDraft($project),
            ],
        ], 201);
    }

    /**
     * Отримати одну чернетку
     *
     * @OA\Get(
     *     path="/v1/my/drafts/{id}",
     *     operationId="showDraft",
     *     tags={"Drafts"},
     *     summary="Отримати чернетку",
     *     description="Повертає детальну інформацію про чернетку",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID чернетки", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Дані чернетки",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        $project = Project::query()
            ->where('user_id', $user->id)
            ->where('status', ProjectStatus::Draft)
            ->with(['stages', 'bonuses'])
            ->findOrFail($id);

        return response()->json([
            'result' => true,
            'data' => [
                'draft' => $this->formatDraftFull($project),
            ],
        ]);
    }

    /**
     * Оновити чернетку
     *
     * @OA\Put(
     *     path="/v1/my/drafts/{id}",
     *     operationId="updateDraft",
     *     tags={"Drafts"},
     *     summary="Оновити чернетку",
     *     description="Оновлює існуючу чернетку",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID чернетки", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="title", type="object"),
     *             @OA\Property(property="short_description", type="object"),
     *             @OA\Property(property="cover", type="string"),
     *             @OA\Property(property="art_category", type="string"),
     *             @OA\Property(property="budget_goal", type="number"),
     *             @OA\Property(property="stages", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="bonuses", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Чернетку оновлено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $project = Project::query()
            ->where('user_id', $user->id)
            ->where('status', ProjectStatus::Draft)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'nullable|array',
            'short_description' => 'nullable|array',
            'cover' => 'nullable|string',
            'art_category' => 'nullable|string',
            'art_subcategory' => 'nullable|string',
            'budget_goal' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|in:UAH,USD,EUR',
            'budget_items' => 'nullable|array',
            'characteristics' => 'nullable|array',
            'additional_info' => 'nullable|array',
            'stages' => 'nullable|array',
            'bonuses' => 'nullable|array',
        ]);

        $project->fill($validated);
        $project->save();

        if (isset($validated['stages'])) {
            $this->syncStages($project, $validated['stages']);
        }

        if (isset($validated['bonuses'])) {
            $this->syncBonuses($project, $validated['bonuses']);
        }

        return response()->json([
            'result' => true,
            'message' => 'Чернетку оновлено',
            'data' => [
                'draft' => $this->formatDraft($project->fresh()),
            ],
        ]);
    }

    /**
     * Видалити чернетку
     *
     * @OA\Delete(
     *     path="/v1/my/drafts/{id}",
     *     operationId="deleteDraft",
     *     tags={"Drafts"},
     *     summary="Видалити чернетку",
     *     description="Видаляє чернетку користувача",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="ID чернетки", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Чернетку видалено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();

        $project = Project::query()
            ->where('user_id', $user->id)
            ->where('status', ProjectStatus::Draft)
            ->findOrFail($id);

        $project->delete();

        return response()->json([
            'result' => true,
            'message' => 'Чернетку видалено',
        ]);
    }

    /**
     * Синхронізувати чернетки з локального сховища
     *
     * @OA\Post(
     *     path="/v1/my/drafts/sync",
     *     operationId="syncDrafts",
     *     tags={"Drafts"},
     *     summary="Синхронізувати чернетки",
     *     description="Синхронізує локальні чернетки з сервером",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="drafts", type="array",
     *
     *                 @OA\Items(type="object",
     *
     *                     @OA\Property(property="local_id", type="string"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(property="data", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Результат синхронізації",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="synced", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="conflicts", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function sync(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'drafts' => 'required|array',
            'drafts.*.local_id' => 'required|string',
            'drafts.*.updated_at' => 'required|date',
            'drafts.*.data' => 'required|array',
        ]);

        $synced = [];
        $conflicts = [];

        foreach ($validated['drafts'] as $localDraft) {
            $localId = $localDraft['local_id'];
            $localUpdatedAt = strtotime($localDraft['updated_at']);

            // Шукаємо існуючий проєкт за local_id
            $existingProject = Project::query()
                ->where('user_id', $user->id)
                ->where('status', ProjectStatus::Draft)
                ->where('additional_info->local_id', $localId)
                ->first();

            if ($existingProject) {
                $serverUpdatedAt = $existingProject->updated_at->timestamp;

                if ($localUpdatedAt > $serverUpdatedAt) {
                    // Локальна версія новіша — оновлюємо сервер
                    $this->updateProjectFromData($existingProject, $localDraft['data']);
                    $synced[] = [
                        'local_id' => $localId,
                        'server_id' => $existingProject->id,
                        'action' => 'updated_server',
                    ];
                } elseif ($localUpdatedAt < $serverUpdatedAt) {
                    // Серверна версія новіша — повертаємо серверні дані
                    $conflicts[] = [
                        'local_id' => $localId,
                        'server_id' => $existingProject->id,
                        'server_data' => $this->formatDraftFull($existingProject),
                        'action' => 'server_newer',
                    ];
                } else {
                    // Однакові — нічого не робимо
                    $synced[] = [
                        'local_id' => $localId,
                        'server_id' => $existingProject->id,
                        'action' => 'no_change',
                    ];
                }
            } else {
                // Створюємо новий проєкт на сервері
                $newProject = $this->createProjectFromData($user->id, $localId, $localDraft['data']);
                $synced[] = [
                    'local_id' => $localId,
                    'server_id' => $newProject->id,
                    'action' => 'created',
                ];
            }
        }

        return response()->json([
            'result' => true,
            'message' => 'Синхронізацію завершено',
            'data' => [
                'synced' => $synced,
                'conflicts' => $conflicts,
            ],
        ]);
    }

    /**
     * Форматування чернетки для відповіді (короткий формат)
     *
     * @return array<string, mixed>
     */
    private function formatDraft(Project $project): array
    {
        return [
            'id' => $project->id,
            'local_id' => $project->additional_info['local_id'] ?? null,
            'title' => $project->title,
            'short_description' => $project->short_description,
            'cover' => $project->cover,
            'art_category' => $project->art_category,
            'art_subcategory' => $project->art_subcategory,
            'budget_goal' => $project->budget_goal,
            'currency' => $project->currency,
            'created_at' => $project->created_at->toIso8601String(),
            'updated_at' => $project->updated_at->toIso8601String(),
        ];
    }

    /**
     * Форматування чернетки (повний формат)
     *
     * @return array<string, mixed>
     */
    private function formatDraftFull(Project $project): array
    {
        return array_merge($this->formatDraft($project), [
            'budget_items' => $project->budget_items,
            'characteristics' => $project->characteristics,
            'additional_info' => $project->additional_info,
            'stages' => $project->stages->map(fn ($stage) => [
                'id' => $stage->id,
                'title' => $stage->title,
                'description' => $stage->description,
                'budget' => $stage->budget,
                'start_date' => $stage->start_date?->toDateString(),
                'end_date' => $stage->end_date?->toDateString(),
                'order' => $stage->order,
            ])->toArray(),
            'bonuses' => $project->bonuses->map(fn ($bonus) => [
                'id' => $bonus->id,
                'title' => $bonus->title,
                'description' => $bonus->description,
                'min_amount' => $bonus->min_amount,
                'max_amount' => $bonus->max_amount,
                'quantity' => $bonus->quantity,
                'order' => $bonus->order,
            ])->toArray(),
        ]);
    }

    /**
     * Синхронізувати етапи проєкту
     *
     * @param  array<int, array<string, mixed>>  $stages
     */
    private function syncStages(Project $project, array $stages): void
    {
        $project->stages()->delete();

        foreach ($stages as $index => $stageData) {
            $project->stages()->create([
                'title' => $stageData['title'] ?? null,
                'description' => $stageData['description'] ?? null,
                'budget' => $stageData['budget'] ?? 0,
                'start_date' => $stageData['start_date'] ?? null,
                'end_date' => $stageData['end_date'] ?? null,
                'order' => $stageData['order'] ?? $index,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Синхронізувати бонуси проєкту
     *
     * @param  array<int, array<string, mixed>>  $bonuses
     */
    private function syncBonuses(Project $project, array $bonuses): void
    {
        $project->bonuses()->delete();

        foreach ($bonuses as $index => $bonusData) {
            $project->bonuses()->create([
                'title' => $bonusData['title'] ?? null,
                'description' => $bonusData['description'] ?? null,
                'min_amount' => $bonusData['min_amount'] ?? $bonusData['from_amount'] ?? 0,
                'max_amount' => $bonusData['max_amount'] ?? $bonusData['to_amount'] ?? null,
                'quantity' => $bonusData['quantity'] ?? null,
                'order' => $bonusData['order'] ?? $index,
            ]);
        }
    }

    /**
     * Оновити проєкт з даних синхронізації
     *
     * @param  array<string, mixed>  $data
     */
    private function updateProjectFromData(Project $project, array $data): void
    {
        if (isset($data['title'])) {
            $project->title = $data['title'];
        }
        if (isset($data['short_description'])) {
            $project->short_description = $data['short_description'];
        }
        if (isset($data['cover'])) {
            $project->cover = $data['cover'];
        }
        if (isset($data['art_category'])) {
            $project->art_category = $data['art_category'];
        }
        if (isset($data['art_subcategory'])) {
            $project->art_subcategory = $data['art_subcategory'];
        }
        if (isset($data['budget_goal'])) {
            $project->budget_goal = $data['budget_goal'];
        }
        if (isset($data['currency'])) {
            $project->currency = $data['currency'];
        }
        if (isset($data['budget_items'])) {
            $project->budget_items = $data['budget_items'];
        }
        if (isset($data['characteristics'])) {
            $project->characteristics = $data['characteristics'];
        }

        $project->save();

        if (isset($data['stages'])) {
            $this->syncStages($project, $data['stages']);
        }
        if (isset($data['bonuses'])) {
            $this->syncBonuses($project, $data['bonuses']);
        }
    }

    /**
     * Створити проєкт з даних синхронізації
     *
     * @param  array<string, mixed>  $data
     */
    private function createProjectFromData(int $userId, string $localId, array $data): Project
    {
        $project = new Project;
        $project->user_id = $userId;
        $project->status = ProjectStatus::Draft;
        $project->code = Project::generateUniqueCode();

        $title = $data['title'] ?? ['uk' => 'Чернетка'];
        $project->slug = Project::generateSlugFromTitle($title);
        $project->title = $title;

        if (isset($data['short_description'])) {
            $project->short_description = $data['short_description'];
        }
        if (isset($data['cover'])) {
            $project->cover = $data['cover'];
        }
        if (isset($data['art_category'])) {
            $project->art_category = $data['art_category'];
        }
        if (isset($data['art_subcategory'])) {
            $project->art_subcategory = $data['art_subcategory'];
        }
        if (isset($data['budget_goal'])) {
            $project->budget_goal = $data['budget_goal'];
        }
        if (isset($data['currency'])) {
            $project->currency = $data['currency'];
        }
        if (isset($data['budget_items'])) {
            $project->budget_items = $data['budget_items'];
        }
        if (isset($data['characteristics'])) {
            $project->characteristics = $data['characteristics'];
        }

        $project->additional_info = ['local_id' => $localId];
        $project->save();

        if (isset($data['stages'])) {
            $this->syncStages($project, $data['stages']);
        }
        if (isset($data['bonuses'])) {
            $this->syncBonuses($project, $data['bonuses']);
        }

        return $project;
    }
}
