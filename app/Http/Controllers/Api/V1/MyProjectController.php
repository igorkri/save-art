<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateProjectRequest;
use App\Http\Requests\Api\V1\UpdateProjectRequest;
use App\Http\Requests\Api\V1\UpdatePublishedProjectRequest;
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
     *     description="Створює новий проєкт у статусі чернетки. Підтримує завантаження обкладинки через multipart/form-data.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"title[uk]", "user_type", "art_category", "currency", "budget_goal"},
     *
     *                 @OA\Property(property="user_type", type="string", enum={"personal", "legal"}, example="personal", description="Тип автора"),
     *                 @OA\Property(property="title[uk]", type="string", example="Назва проєкту", description="Назва українською (обов'язкова)"),
     *                 @OA\Property(property="title[en]", type="string", example="Project name", description="Назва англійською (опціонально)"),
     *                 @OA\Property(property="short_description[uk]", type="string", example="Короткий опис"),
     *                 @OA\Property(property="short_description[en]", type="string", example="Short description"),
     *                 @OA\Property(property="cover", type="string", format="binary", description="Обкладинка проєкту (JPG, PNG, до 15MB)"),
     *                 @OA\Property(property="art_category", type="string", enum={"scenic", "visual", "fine_art", "literature", "music", "other"}, example="visual"),
     *                 @OA\Property(property="art_subcategory", type="string", nullable=true, example="painting"),
     *                 @OA\Property(property="tags[uk]", type="string", example="живопис, арт"),
     *                 @OA\Property(property="tags[en]", type="string", example="painting, art"),
     *                 @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, example="UAH"),
     *                 @OA\Property(property="budget_goal", type="number", format="float", minimum=100, example=50000),
     *                 @OA\Property(property="estimated_days", type="integer", minimum=1, maximum=365, example=90)
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"title", "user_type", "art_category", "currency", "budget_goal"},
     *
     *                 @OA\Property(property="user_type", type="string", enum={"personal", "legal"}, example="personal", description="Тип автора"),
     *                 @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
     *                 @OA\Property(property="short_description", ref="#/components/schemas/LocalizedString"),
     *                 @OA\Property(property="art_category", type="string", enum={"scenic", "visual", "fine_art", "literature", "music", "other"}, example="visual"),
     *                 @OA\Property(property="art_subcategory", type="string", nullable=true, example="painting"),
     *                 @OA\Property(property="tags", ref="#/components/schemas/LocalizedString"),
     *                 @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, example="UAH"),
     *                 @OA\Property(property="budget_goal", type="number", format="float", minimum=100, example=50000),
     *                 @OA\Property(property="estimated_days", type="integer", minimum=1, maximum=365, example=90),
     *                 @OA\Property(property="budget_items", type="array", description="Статті бюджету",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="name", type="object", description="Назва статті (мультимовна)",
     *                             @OA\Property(property="uk", type="string", example="Матеріали"),
     *                             @OA\Property(property="en", type="string", example="Materials")
     *                         ),
     *                         @OA\Property(property="amount", type="number", example=15000, description="Сума у валюті проєкту")
     *                     )
     *                 ),
     *                 @OA\Property(property="characteristics", type="array", nullable=true, description="Характеристики проєкту",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="name", type="object", description="Назва характеристики",
     *                             @OA\Property(property="uk", type="string", example="Розмір"),
     *                             @OA\Property(property="en", type="string", example="Size")
     *                         ),
     *                         @OA\Property(property="value", type="object", description="Значення характеристики",
     *                             @OA\Property(property="uk", type="string", example="100x150 см"),
     *                             @OA\Property(property="en", type="string", example="100x150 cm")
     *                         )
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
     *     summary="Оновити проєкт (повне оновлення)",
     *     description="Оновлює дані проєкту. Доступно тільки для чернеток та відхилених. Підтримує завантаження обкладинки через multipart/form-data.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer"), example=1),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="title[uk]", type="string", example="Нова назва"),
     *                 @OA\Property(property="title[en]", type="string", example="New title"),
     *                 @OA\Property(property="short_description[uk]", type="string"),
     *                 @OA\Property(property="short_description[en]", type="string"),
     *                 @OA\Property(property="cover", type="string", format="binary", description="Обкладинка проєкту (JPG, PNG, до 15MB)"),
     *                 @OA\Property(property="art_category", type="string", enum={"scenic", "visual", "fine_art", "literature", "music", "other"}),
     *                 @OA\Property(property="budget_goal", type="number", format="float"),
     *                 @OA\Property(property="estimated_days", type="integer")
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
     *                 @OA\Property(property="short_description", ref="#/components/schemas/LocalizedString"),
     *                 @OA\Property(property="art_category", type="string", enum={"scenic", "visual", "fine_art", "literature", "music", "other"}),
     *                 @OA\Property(property="budget_goal", type="number", format="float"),
     *                 @OA\Property(property="estimated_days", type="integer")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Проєкт оновлено", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Project"))),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник проєкту або проєкт не є чернеткою"),
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
     * Часткове оновлення опублікованого проєкту (03.4.2.2)
     *
     * @OA\Patch(
     *     path="/v1/my/projects/{project}",
     *     operationId="updateMyProjectPartially",
     *     tags={"My Projects"},
     *     summary="Часткове оновлення опублікованого проєкту (03.4.2.2)",
     *     description="Дозволяє редагувати назву, опис, теги та обкладинку опублікованого проєкту. Бюджет та категорію змінити не можна. Підтримує завантаження обкладинки через multipart/form-data.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer"), example=1),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="title[uk]", type="string", example="Нова назва", description="Назва українською"),
     *                 @OA\Property(property="title[en]", type="string", example="New title", description="Назва англійською"),
     *                 @OA\Property(property="short_description[uk]", type="string", description="Короткий опис українською"),
     *                 @OA\Property(property="short_description[en]", type="string", description="Короткий опис англійською"),
     *                 @OA\Property(property="cover", type="string", format="binary", description="Нова обкладинка (JPG, PNG, до 15MB)"),
     *                 @OA\Property(property="tags[uk]", type="string", example="живопис, арт", description="Теги українською"),
     *                 @OA\Property(property="tags[en]", type="string", example="painting, art", description="Теги англійською"),
     *                 @OA\Property(property="additional_info[uk]", type="string", description="Додаткова інформація українською"),
     *                 @OA\Property(property="additional_info[en]", type="string", description="Додаткова інформація англійською")
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="title", ref="#/components/schemas/LocalizedString", description="Нова назва проєкту"),
     *                 @OA\Property(property="short_description", ref="#/components/schemas/LocalizedString", description="Новий короткий опис"),
     *                 @OA\Property(property="tags", ref="#/components/schemas/LocalizedString", description="Нові теги"),
     *                 @OA\Property(property="additional_info", ref="#/components/schemas/LocalizedString", description="Нова додаткова інформація")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Проєкт оновлено",
     *
     *         @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Project"))
     *     ),
     *
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник проєкту або проєкт не опублікований"),
     *     @OA\Response(response=422, description="Помилка валідації")
     * )
     */
    public function updatePartial(UpdatePublishedProjectRequest $request, Project $project): ProjectResource
    {
        $data = $request->validated();

        // Обробка обкладинки
        if ($request->hasFile('cover')) {
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
     * Завантажити файли фінального результату (03.4.4)
     *
     * @OA\Post(
     *     path="/v1/my/projects/{project}/final-result/upload",
     *     operationId="uploadFinalResult",
     *     tags={"My Projects"},
     *     summary="Завантажити файли фінального результату (03.4.4)",
     *     description="Завантажує файли фінального результату: зображення, галерею, відео або документ. Доступно для проєктів в роботі або завершених.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer"), example=1),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 required={"type", "files"},
     *
     *                 @OA\Property(property="type", type="string", enum={"image", "gallery", "video", "document"}, example="gallery", description="Тип результату: image (одне зображення), gallery (кілька зображень), video (відео-файл), document (PDF або інший документ)"),
     *                 @OA\Property(property="files", type="array", description="Файли (до 10 файлів, до 50MB кожен)",
     *
     *                     @OA\Items(type="string", format="binary")
     *                 ),
     *
     *                 @OA\Property(property="description[uk]", type="string", example="Опис результату українською"),
     *                 @OA\Property(property="description[en]", type="string", example="Result description in English")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Файли завантажено",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Фінальний результат збережено."),
     *             @OA\Property(property="data", ref="#/components/schemas/Project")
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function uploadFinalResult(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Дозволяємо завантаження для проєктів в роботі або завершених (для оновлення)
        if (! in_array($project->status, [ProjectStatus::InProgress, ProjectStatus::Completed])) {
            return response()->json([
                'message' => 'Завантажувати фінальний результат можна лише для проєктів в роботі або завершених.',
            ], 422);
        }

        $request->validate([
            'type' => ['required', 'in:image,gallery,video,document'],
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'max:51200'], // 50MB max
            'description' => ['nullable', 'array'],
            'description.uk' => ['nullable', 'string', 'max:2000'],
            'description.en' => ['nullable', 'string', 'max:2000'],
        ], [
            'files.required' => 'Завантажте хоча б один файл',
            'files.min' => 'Завантажте хоча б один файл',
            'files.max' => 'Максимум 10 файлів',
            'files.*.max' => 'Максимальний розмір файлу 50MB',
        ]);

        $type = $request->input('type');

        // Додаткова валідація типів файлів залежно від type
        $allowedMimes = match ($type) {
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'gallery' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'video' => ['mp4', 'mov', 'avi', 'webm', 'mkv'],
            'document' => ['pdf', 'doc', 'docx'],
        };

        foreach ($request->file('files') as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            if (! in_array($extension, $allowedMimes)) {
                $allowedList = implode(', ', array_map('strtoupper', $allowedMimes));

                return response()->json([
                    'message' => "Для типу '{$type}' дозволені формати: {$allowedList}",
                    'errors' => ['files' => ["Недопустимий формат файлу. Дозволені: {$allowedList}"]],
                ], 422);
            }
        }

        $finalResult = [
            'type' => $type,
            'description' => $request->input('description'),
            'uploaded_at' => now()->toISOString(),
        ];

        // Обробка файлів
        $uploadedFiles = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store("projects/{$project->id}/final-result", 'public');
            $uploadedFiles[] = [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        }

        // Структура залежить від кількості файлів
        // Один файл -> file, кілька файлів -> files
        if (count($uploadedFiles) === 1) {
            $finalResult['file'] = $uploadedFiles[0];
        } else {
            $finalResult['files'] = $uploadedFiles;
        }

        $project->update([
            'final_result' => $finalResult,
        ]);

        return response()->json([
            'message' => 'Фінальний результат збережено.',
            'data' => new ProjectResource($project->fresh()),
        ]);
    }

    /**
     * Завершити проєкт (03.4.5)
     *
     * @OA\Post(
     *     path="/v1/my/projects/{project}/complete",
     *     operationId="completeProject",
     *     tags={"My Projects"},
     *     summary="Завершити проєкт (03.4.5)",
     *     description="Позначає проєкт як завершений. Перед викликом потрібно завантажити фінальний результат через /final-result/upload.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer"), example=1),
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
     *     @OA\Response(response=422, description="Проєкт не в статусі 'в роботі' або немає фінального результату")
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

        // Перевіряємо, чи є фінальний результат
        if (empty($project->final_result)) {
            return response()->json([
                'message' => 'Спочатку завантажте фінальний результат через /final-result/upload.',
            ], 422);
        }

        $project->update([
            'status' => ProjectStatus::Completed,
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Проєкт завершено.',
            'data' => new ProjectResource($project),
        ]);
    }
}
