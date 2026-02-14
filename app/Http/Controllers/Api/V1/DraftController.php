<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

class DraftController extends Controller
{
    public function __construct(
        private ImageProcessingService $imageProcessor
    ) {}

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
     *             @OA\Property(property="user_type", type="string", enum={"personal", "legal"}, description="Тип користувача"),
     *             @OA\Property(property="title", type="object",
     *                 @OA\Property(property="uk", type="string"),
     *                 @OA\Property(property="en", type="string")
     *             ),
     *             @OA\Property(property="short_description", type="object",
     *                 @OA\Property(property="uk", type="string"),
     *                 @OA\Property(property="en", type="string")
     *             ),
     *             @OA\Property(property="tags", type="object",
     *                 @OA\Property(property="uk", type="string"),
     *                 @OA\Property(property="en", type="string")
     *             ),
     *             @OA\Property(property="cover", type="string"),
     *             @OA\Property(property="art_category", type="string", enum={"scenic", "visual", "fine_art", "literature", "music", "other"}),
     *             @OA\Property(property="art_subcategory", type="string"),
     *             @OA\Property(property="budget_goal", type="number"),
     *             @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}),
     *             @OA\Property(property="estimated_days", type="integer", description="Орієнтовна кількість днів"),
     *             @OA\Property(property="budget_items", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="name", type="object"),
     *                 @OA\Property(property="amount", type="number")
     *             )),
     *             @OA\Property(property="characteristics", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="name", type="object"),
     *                 @OA\Property(property="value", type="object")
     *             )),
     *             @OA\Property(property="content_blocks", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="type", type="string", enum={"heading", "paragraph", "image"}),
     *                 @OA\Property(property="heading_level", type="string"),
     *                 @OA\Property(property="heading_text", type="object"),
     *                 @OA\Property(property="paragraph_text", type="object"),
     *                 @OA\Property(property="image", type="string"),
     *                 @OA\Property(property="image_alt", type="object")
     *             )),
     *             @OA\Property(property="stages", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="title", type="object"),
     *                 @OA\Property(property="description", type="object"),
     *                 @OA\Property(property="days_planned", type="integer"),
     *                 @OA\Property(property="budget_planned", type="number")
     *             )),
     *             @OA\Property(property="bonuses", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="title", type="object"),
     *                 @OA\Property(property="description", type="object"),
     *                 @OA\Property(property="min_donation", type="number"),
     *                 @OA\Property(property="quantity", type="integer", nullable=true)
     *             ))
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
            'user_type' => 'nullable|string|in:personal,legal',
            'title' => 'nullable|array',
            'title.uk' => 'nullable|string|max:255',
            'title.en' => 'nullable|string|max:255',
            'short_description' => 'nullable|array',
            'short_description.uk' => 'nullable|string',
            'short_description.en' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.uk' => 'nullable|string',
            'tags.en' => 'nullable|string',
            'cover' => 'nullable|string',
            'art_category' => 'nullable|string',
            'art_subcategory' => 'nullable|string',
            'budget_goal' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|in:UAH,USD,EUR',
            'estimated_days' => 'nullable|integer|min:1',
            'budget_items' => 'nullable|array',
            'characteristics' => 'nullable|array',
            'content_blocks' => 'nullable|array',
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
        if (isset($validated['user_type'])) {
            $project->user_type = $validated['user_type'];
        }
        if (isset($validated['title'])) {
            $project->title = $validated['title'];
        }
        if (isset($validated['short_description'])) {
            $project->short_description = $validated['short_description'];
        }
        if (isset($validated['tags'])) {
            $project->tags = $validated['tags'];
        }
        if (isset($validated['cover'])) {
            $project->cover = $this->imageProcessor->processCover($validated['cover'], $project->cover);
        }
        if (isset($validated['art_category'])) {
            $project->art_category = $this->normalizeArtCategory($validated['art_category']);
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
        if (isset($validated['estimated_days'])) {
            $project->estimated_days = $validated['estimated_days'];
        }
        if (isset($validated['budget_items'])) {
            $project->budget_items = $validated['budget_items'];
        }
        if (isset($validated['characteristics'])) {
            $project->characteristics = $validated['characteristics'];
        }
        if (isset($validated['content_blocks'])) {
            $project->content_blocks = $this->imageProcessor->processContentBlocks(
                $validated['content_blocks'],
                $project->content_blocks
            );
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
            'user_type' => 'nullable|string|in:personal,legal',
            'title' => 'nullable|array',
            'short_description' => 'nullable|array',
            'tags' => 'nullable|array',
            'cover' => 'nullable|string',
            'art_category' => 'nullable|string',
            'art_subcategory' => 'nullable|string',
            'budget_goal' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|in:UAH,USD,EUR',
            'estimated_days' => 'nullable|integer|min:1',
            'budget_items' => 'nullable|array',
            'characteristics' => 'nullable|array',
            'content_blocks' => 'nullable|array',
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
            'user_type' => $project->user_type?->value ?? 'personal',
            'title' => $project->title,
            'short_description' => $project->short_description,
            'tags' => $project->tags,
            'cover' => $project->cover ? Storage::url($project->cover) : null,
            'art_category' => $project->art_category instanceof \BackedEnum ? $project->art_category->value : $project->art_category,
            'art_subcategory' => $project->art_subcategory,
            'budget_goal' => $project->budget_goal ? (float) $project->budget_goal : null,
            'currency' => $project->currency?->value ?? 'UAH',
            'estimated_days' => $project->estimated_days,
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
            'content_blocks' => $project->content_blocks ?? [],
            'additional_info' => $project->additional_info,
            'stages' => $project->stages->map(fn ($stage) => [
                'id' => $stage->id,
                'title' => $stage->title,
                'description' => $stage->description,
                'days_planned' => $stage->days_planned,
                'budget_planned' => $stage->budget_planned ? (float) $stage->budget_planned : null,
                'order' => $stage->order,
            ])->toArray(),
            'bonuses' => $project->bonuses->map(fn ($bonus) => [
                'id' => $bonus->id,
                'title' => $bonus->title,
                'description' => $bonus->description,
                'min_donation' => $bonus->min_donation ? (float) $bonus->min_donation : null,
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
                'days_planned' => $stageData['days_planned'] ?? null,
                'budget_planned' => $stageData['budget_planned'] ?? $stageData['budget'] ?? 0,
                'order' => $stageData['order'] ?? $index,
                'status' => 'planned',
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
                'min_donation' => $bonusData['min_donation'] ?? $bonusData['min_amount'] ?? 0,
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
        if (isset($data['user_type'])) {
            $project->user_type = $data['user_type'];
        }
        if (isset($data['title'])) {
            $project->title = $data['title'];
        }
        if (isset($data['short_description'])) {
            $project->short_description = $data['short_description'];
        }
        if (isset($data['tags'])) {
            $project->tags = $data['tags'];
        }
        if (isset($data['cover'])) {
            $project->cover = $this->imageProcessor->processCover($data['cover'], $project->cover);
        }
        if (isset($data['art_category'])) {
            $project->art_category = $this->normalizeArtCategory($data['art_category']);
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
        if (isset($data['estimated_days'])) {
            $project->estimated_days = $data['estimated_days'];
        }
        if (isset($data['budget_items'])) {
            $project->budget_items = $data['budget_items'];
        }
        if (isset($data['characteristics'])) {
            $project->characteristics = $data['characteristics'];
        }
        if (isset($data['content_blocks'])) {
            $project->content_blocks = $this->imageProcessor->processContentBlocks(
                $data['content_blocks'],
                $project->content_blocks
            );
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

        if (isset($data['user_type'])) {
            $project->user_type = $data['user_type'];
        }
        if (isset($data['short_description'])) {
            $project->short_description = $data['short_description'];
        }
        if (isset($data['tags'])) {
            $project->tags = $data['tags'];
        }
        if (isset($data['cover'])) {
            $project->cover = $this->imageProcessor->processCover($data['cover']);
        }
        if (isset($data['art_category'])) {
            $project->art_category = $this->normalizeArtCategory($data['art_category']);
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
        if (isset($data['estimated_days'])) {
            $project->estimated_days = $data['estimated_days'];
        }
        if (isset($data['budget_items'])) {
            $project->budget_items = $data['budget_items'];
        }
        if (isset($data['characteristics'])) {
            $project->characteristics = $data['characteristics'];
        }
        if (isset($data['content_blocks'])) {
            $project->content_blocks = $this->imageProcessor->processContentBlocks($data['content_blocks']);
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

    /**
     * Нормалізація значення art_category
     * Перетворює варіанти з фронтенду в валідні значення enum
     */
    private function normalizeArtCategory(?string $category): ?string
    {
        if ($category === null) {
            return null;
        }

        // Маппінг альтернативних значень
        $mapping = [
            'fine_arts' => 'fine_art',
            'finearts' => 'fine_art',
            'fineart' => 'fine_art',
            'performing_arts' => 'scenic',
            'performingarts' => 'scenic',
            'visual_arts' => 'visual',
            'visualarts' => 'visual',
        ];

        $normalized = strtolower(trim($category));

        return $mapping[$normalized] ?? $category;
    }
}
