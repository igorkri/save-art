<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateProjectRequest;
use App\Http\Requests\Api\V1\UpdateProjectRequest;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="My Projects",
 *     description="API для управління власними проєктами (кабінет митця)"
 * )
 */
class MyProjectController extends Controller
{
    /**
     * Отримати список власних проєктів
     *
     * @OA\Get(
     *     path="/v1/my/projects",
     *     operationId="getMyProjects",
     *     tags={"My Projects"},
     *     summary="Список моїх проєктів",
     *     description="Повертає список проєктів авторизованого користувача (всі статуси)",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Фільтр по статусу",
     *
     *         @OA\Schema(type="string", enum={"draft", "moderation", "announced", "in_progress", "paused", "completed", "sold", "rejected"}),
     *         example="draft"
     *     ),
     *
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15, maximum=50)),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список проєктів",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProjectList")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Project::query()
            ->with('user')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        // Фільтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = min($request->input('per_page', 15), 50);
        $projects = $query->paginate($perPage);

        return ProjectListResource::collection($projects);
    }

    /**
     * Створити новий проєкт (чернетку)
     *
     * @OA\Post(
     *     path="/v1/my/projects",
     *     operationId="createProject",
     *     tags={"My Projects"},
     *     summary="Створити проєкт",
     *     description="Створює новий проєкт у статусі чернетки",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"title", "user_type", "art_category", "currency", "budget_goal"},
     *
     *             @OA\Property(property="user_type", type="string", enum={"personal", "legal"}, example="personal", description="Тип автора"),
     *             @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
     *             @OA\Property(property="short_description", ref="#/components/schemas/LocalizedString"),
     *             @OA\Property(property="art_category", type="string", enum={"scenic", "visual", "fine_art", "literature", "music", "other"}, example="visual"),
     *             @OA\Property(property="art_subcategory", type="string", nullable=true, example="painting"),
     *             @OA\Property(property="tags", ref="#/components/schemas/LocalizedString"),
     *             @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, example="UAH"),
     *             @OA\Property(property="budget_goal", type="number", format="float", minimum=100, example=50000),
     *             @OA\Property(property="estimated_days", type="integer", minimum=1, maximum=365, example=90),
     *             @OA\Property(property="budget_items", type="array", description="Статті бюджету",
     *
     *                 @OA\Items(type="object",
     *
     *                     @OA\Property(property="name", type="object", description="Назва статті (мультимовна)",
     *                         @OA\Property(property="uk", type="string", example="Матеріали"),
     *                         @OA\Property(property="en", type="string", example="Materials")
     *                     ),
     *                     @OA\Property(property="amount", type="number", example=15000, description="Сума у валюті проєкту")
     *                 )
     *             ),
     *             @OA\Property(property="characteristics", type="array", nullable=true, description="Характеристики проєкту",
     *
     *                 @OA\Items(type="object",
     *
     *                     @OA\Property(property="name", type="object", description="Назва характеристики",
     *                         @OA\Property(property="uk", type="string", example="Розмір"),
     *                         @OA\Property(property="en", type="string", example="Size")
     *                     ),
     *                     @OA\Property(property="value", type="object", description="Значення характеристики",
     *                         @OA\Property(property="uk", type="string", example="100x150 см"),
     *                         @OA\Property(property="en", type="string", example="100x150 cm")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Проєкт створено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Project")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=422, description="Помилка валідації", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function store(CreateProjectRequest $request): ProjectResource
    {
        $data = $request->validated();

        // Генеруємо унікальний код та slug
        $data['code'] = strtoupper(Str::random(8));
        $data['slug'] = Str::slug($data['title']['uk'] ?? 'project').'-'.Str::random(6);

        // Обробка обкладинки
        if ($request->hasFile('cover')) {
            $data['cover'] = $request->file('cover')->store('projects/covers', 'public');
        }

        // Встановлюємо початкові статуси
        $data['user_id'] = $request->user()->id;
        $data['status'] = ProjectStatus::Draft;
        $data['status_moderation'] = ModerationStatus::Pending;
        $data['budget_collected'] = 0;
        $data['likes_count'] = 0;
        $data['donors_count'] = 0;

        $project = Project::create($data);

        return new ProjectResource($project->load(['user', 'stages', 'bonuses']));
    }

    /**
     * Отримати деталі власного проєкту
     *
     * @OA\Get(
     *     path="/v1/my/projects/{project}",
     *     operationId="getMyProject",
     *     tags={"My Projects"},
     *     summary="Деталі мого проєкту",
     *     description="Повертає повну інформацію про власний проєкт",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer"), example=1),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Деталі проєкту",
     *
     *         @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Project"))
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник проєкту"),
     *     @OA\Response(response=404, description="Проєкт не знайдено")
     * )
     */
    public function show(Request $request, Project $project): ProjectResource|JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return new ProjectResource($project->load(['user', 'stages', 'bonuses']));
    }

    /**
     * Оновити проєкт
     *
     * @OA\Put(
     *     path="/v1/my/projects/{project}",
     *     operationId="updateMyProject",
     *     tags={"My Projects"},
     *     summary="Оновити проєкт",
     *     description="Оновлює дані проєкту. Доступно тільки для чернеток та відхилених.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer"), example=1),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
     *             @OA\Property(property="short_description", ref="#/components/schemas/LocalizedString"),
     *             @OA\Property(property="art_category", type="string", enum={"scenic", "visual", "fine_art", "literature", "music", "other"}),
     *             @OA\Property(property="budget_goal", type="number", format="float"),
     *             @OA\Property(property="estimated_days", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Проєкт оновлено", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Project"))),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник проєкту"),
     *     @OA\Response(response=422, description="Помилка валідації")
     * )
     */
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $data = $request->validated();

        // Обробка обкладинки
        if ($request->hasFile('cover')) {
            // Видаляємо стару обкладинку
            if ($project->cover) {
                Storage::disk('public')->delete($project->cover);
            }
            $data['cover'] = $request->file('cover')->store('projects/covers', 'public');
        }

        $project->update($data);

        return new ProjectResource($project->load(['user', 'stages', 'bonuses']));
    }

    /**
     * Видалити проєкт (тільки чернетки)
     *
     * @OA\Delete(
     *     path="/v1/my/projects/{project}",
     *     operationId="deleteMyProject",
     *     tags={"My Projects"},
     *     summary="Видалити проєкт",
     *     description="Видаляє проєкт. Доступно тільки для чернеток.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer"), example=1),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Проєкт видалено",
     *
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Проєкт видалено"))
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник проєкту"),
     *     @OA\Response(response=422, description="Неможливо видалити (не чернетка)")
     * )
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($project->status !== ProjectStatus::Draft) {
            return response()->json([
                'message' => 'Можна видалити лише чернетки. Для інших проєктів зверніться до модератора.',
            ], 422);
        }

        // Видаляємо обкладинку
        if ($project->cover) {
            Storage::disk('public')->delete($project->cover);
        }

        $project->delete();

        return response()->json(['message' => 'Проєкт видалено']);
    }

    /**
     * Відправити проєкт на модерацію
     *
     * @OA\Post(
     *     path="/v1/my/projects/{project}/submit",
     *     operationId="submitProject",
     *     tags={"My Projects"},
     *     summary="Відправити на модерацію",
     *     description="Відправляє проєкт на розгляд модераторам. Доступно для чернеток та відхилених.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer"), example=1),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Проєкт відправлено на модерацію",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Проєкт відправлено на модерацію."),
     *             @OA\Property(property="data", ref="#/components/schemas/Project")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник проєкту"),
     *     @OA\Response(response=422, description="Не заповнені обов'язкові поля або невалідний статус")
     * )
     */
    public function submit(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! in_array($project->status, [ProjectStatus::Draft, ProjectStatus::Rejected])) {
            return response()->json([
                'message' => 'На модерацію можна відправити лише чернетку або відхилений проєкт.',
            ], 422);
        }

        // Валідація обов'язкових полів перед відправкою
        $errors = [];
        if (empty($project->title['uk'])) {
            $errors['title'] = ['Назва проєкту є обов\'язковою'];
        }
        if (empty($project->art_category)) {
            $errors['art_category'] = ['Оберіть галузь мистецтва'];
        }
        if (empty($project->budget_goal)) {
            $errors['budget_goal'] = ['Вкажіть ціль збору'];
        }

        if (! empty($errors)) {
            return response()->json([
                'message' => 'Заповніть обов\'язкові поля перед відправкою на модерацію.',
                'errors' => $errors,
            ], 422);
        }

        $project->update([
            'status' => ProjectStatus::Moderation,
            'status_moderation' => ModerationStatus::Pending,
        ]);

        return response()->json([
            'message' => 'Проєкт відправлено на модерацію.',
            'data' => new ProjectResource($project),
        ]);
    }

    /**
     * Завершити проєкт (додати фінальний результат)
     *
     * @OA\Post(
     *     path="/v1/my/projects/{project}/complete",
     *     operationId="completeProject",
     *     tags={"My Projects"},
     *     summary="Завершити проєкт",
     *     description="Позначає проєкт як завершений та додає фінальний результат (зображення/відео/галерея)",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer"), example=1),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"final_result"},
     *
     *             @OA\Property(property="final_result", type="object",
     *                 required={"type"},
     *                 @OA\Property(property="type", type="string", enum={"image", "gallery", "video", "link"}, example="video"),
     *                 @OA\Property(property="url", type="string", example="https://youtube.com/watch?v=..."),
     *                 @OA\Property(property="images", type="array", @OA\Items(type="string"), description="Для галереї"),
     *                 @OA\Property(property="description", type="string", example="Фінальний результат проєкту")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Проєкт завершено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Проєкт завершено."),
     *             @OA\Property(property="data", ref="#/components/schemas/Project")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник проєкту"),
     *     @OA\Response(response=422, description="Проєкт не в статусі 'в роботі'")
     * )
     */
    public function complete(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($project->status !== ProjectStatus::InProgress) {
            return response()->json([
                'message' => 'Завершити можна лише проєкт в роботі.',
            ], 422);
        }

        $request->validate([
            'final_result' => ['required', 'array'],
            'final_result.type' => ['required', 'in:image,gallery,video,link'],
        ]);

        $project->update([
            'status' => ProjectStatus::Completed,
            'final_result' => $request->input('final_result'),
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Проєкт завершено.',
            'data' => new ProjectResource($project),
        ]);
    }
}
