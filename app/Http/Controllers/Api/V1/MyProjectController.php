<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Enums\StageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProjectRequest;
use App\Http\Requests\Api\V1\UpdateProjectRequest;
use App\Http\Requests\Api\V1\UpdatePublishedProjectRequest;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\ArtCategory;
use App\Models\Project;
use App\Models\ProjectBonus;
use App\Models\ProjectStage;
use App\Services\ImageProcessingService;
use App\Services\ProjectWorkflowService;
use App\Support\ProjectCategoryParameterValues;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
    public function __construct(
        private ImageProcessingService $imageProcessor,
        private ProjectWorkflowService $workflowService
    ) {}

    /**
     * Отримати список власних проєктів
     *
     * @OA\Get(
     *     path="/v1/my/projects",
     *     operationId="getMyProjects",
     *     tags={"My Projects"},
     *     summary="Список моїх проєктів",
     *     description="Повертає список проєктів авторизованого користувача (всі статуси)",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Фільтр по статусу",
     *
     *         @OA\Schema(type="string", enum={"new", "draft", "moderation", "announced", "in_progress", "paused", "completed", "sold", "rejected"}),
     *         example="draft"
     *     ),
     *
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15, maximum=50)),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
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
            ->with(['user.profileLegal', 'projectParameters.parameter', 'projectParameters.parameterValue'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        // Фільтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->whereIn('status', [
                ProjectStatus::Moderation,
                ProjectStatus::Announced,
                ProjectStatus::InProgress,
                ProjectStatus::Paused,
                ProjectStatus::Completed,
                ProjectStatus::Sold,
                ProjectStatus::Rejected,
            ]);

        }

        $perPage = min($request->input('per_page', 15), 50);
        $projects = $query->paginate($perPage);

        return ProjectListResource::collection($projects);
    }

    /**
     * Створити або оновити проєкт з різними статусами
     *
     * @OA\Post(
     *     path="/v1/my/projects",
     *     operationId="storeProject",
     *     tags={"My Projects"},
     *     summary="Створити проєкт (від порожнього {} до повного)",
     *     description="Створює новий проєкт або оновлює існуючий по local_id. Мінімальний запит: {} (порожній об'єкт) створює проєкт зі статусом 'new'. Статуси: 'new'/'draft' - мінімальна валідація, 'moderation' - повна валідація. Повна документація: docs/api/project-create-examples.json",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\RequestBody(
     *         required=false,
     *         description="Дані проєкту. Мінімальний запит: {} створює проєкт зі статусом 'new'. Для статусу 'moderation' обов'язкові: user_type, title.uk, art_category, currency, budget_goal",
     *
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "status": "moderation",
     *                 "local_id": "mobile-draft-12345",
     *                 "user_type": "personal",
     *                 "title": {
     *                     "uk": "Картина 'Світанок над Дніпром'",
     *                     "en": "Painting 'Dawn over Dnipro'"
     *                 },
     *                 "short_description": {
     *                     "uk": "Олійний живопис на полотні розміром 100x150 см, присвячений красі українських пейзажів",
     *                     "en": "Oil painting on canvas 100x150 cm, dedicated to the beauty of Ukrainian landscapes"
     *                 },
     *                 "cover": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD...",
     *                 "art_category": "fine_art",
     *                 "art_subcategory": "painting",
     *                 "tags": {
     *                     "uk": "живопис, пейзаж, Україна, олія, природа",
     *                     "en": "painting, landscape, Ukraine, oil, nature"
     *                 },
     *                 "currency": "UAH",
     *                 "budget_goal": 75000,
     *                 "estimated_days": 90,
     *                 "budget_items": {
     *                     {"name": {"uk": "Фарби та пензлі", "en": "Paints and brushes"}, "amount": 15000},
     *                     {"name": {"uk": "Полотно та підрамник", "en": "Canvas and stretcher"}, "amount": 8000},
     *                     {"name": {"uk": "Рама для картини", "en": "Picture frame"}, "amount": 12000},
     *                     {"name": {"uk": "Оренда студії (3 міс)", "en": "Studio rent (3 months)"}, "amount": 20000},
     *                     {"name": {"uk": "Доставка та пакування", "en": "Delivery and packaging"}, "amount": 5000},
     *                     {"name": {"uk": "Комісія платформи (13%)", "en": "Platform commission (13%)"}, "amount": 10000}
     *                 },
     *                 "additional_info": {
     *                     "uk": "Картина буде готова протягом 3 місяців. Доставка по Україні безкоштовна.",
     *                     "en": "The painting will be ready within 3 months. Free delivery within Ukraine."
     *                 },
     *                 "content_blocks": {
     *                     {"type": "heading", "heading_level": "h2", "heading_text": {"uk": "Про проєкт", "en": "About the Project"}},
     *                     {"type": "paragraph", "paragraph_text": {"uk": "Цей проєкт народився з мого глибокого захоплення українськими пейзажами...", "en": "This project was born from my deep fascination with Ukrainian landscapes..."}},
     *                     {"type": "image", "image": "data:image/jpeg;base64,/9j/4AAQ...", "image_alt": {"uk": "Ескіз картини", "en": "Sketch"}}
     *                 },
     *                 "stages": {
     *                     {"title": {"uk": "Підготовка ескізів", "en": "Sketch preparation"}, "description": {"uk": "Створення детальних ескізів", "en": "Creating detailed sketches"}, "days_planned": 14, "budget_planned": 5000, "order": 1},
     *                     {"title": {"uk": "Підготовка полотна", "en": "Canvas preparation"}, "description": {"uk": "Натягування полотна, ґрунтування", "en": "Stretching canvas, priming"}, "days_planned": 7, "budget_planned": 8000, "order": 2},
     *                     {"title": {"uk": "Основна робота", "en": "Main work"}, "description": {"uk": "Написання картини олійними фарбами", "en": "Painting with oil paints"}, "days_planned": 45, "budget_planned": 35000, "order": 3},
     *                     {"title": {"uk": "Завершення та фіксація", "en": "Finishing"}, "description": {"uk": "Лакування, підготовка до оформлення", "en": "Varnishing, preparation for framing"}, "days_planned": 14, "budget_planned": 17000, "order": 4},
     *                     {"title": {"uk": "Доставка", "en": "Delivery"}, "description": {"uk": "Пакування та доставка", "en": "Packaging and delivery"}, "days_planned": 10, "budget_planned": 10000, "order": 5}
     *                 },
     *                 "bonuses": {
     *                     {"title": {"uk": "Подяка на сайті", "en": "Thanks on website"}, "description": {"uk": "Ваше ім'я у списку меценатів", "en": "Your name in patrons list"}, "min_donation": 100, "max_donation": 499, "quantity": null, "order": 1},
     *                     {"title": {"uk": "Листівка з репродукцією", "en": "Postcard"}, "description": {"uk": "Авторська листівка з репродукцією", "en": "Author's postcard with reproduction"}, "min_donation": 500, "max_donation": 1499, "quantity": 100, "order": 2},
     *                     {"title": {"uk": "Підписаний принт А4", "en": "Signed A4 print"}, "description": {"uk": "Принт з підписом автора", "en": "Print with author's signature"}, "min_donation": 1500, "max_donation": 2999, "quantity": 50, "order": 3},
     *                     {"title": {"uk": "Принт А3 + сертифікат", "en": "A3 print + certificate"}, "description": {"uk": "Принт А3 з сертифікатом", "en": "A3 print with certificate"}, "min_donation": 3000, "max_donation": 4999, "quantity": 25, "order": 4},
     *                     {"title": {"uk": "Відвідування студії", "en": "Studio visit"}, "description": {"uk": "Екскурсія до студії", "en": "Studio tour"}, "min_donation": 5000, "max_donation": 9999, "quantity": 10, "order": 5},
     *                     {"title": {"uk": "Оригінальний ескіз", "en": "Original sketch"}, "description": {"uk": "Ескіз з підписом", "en": "Sketch with signature"}, "min_donation": 10000, "max_donation": null, "quantity": 5, "order": 6}
     *                 }
     *             },
     *
     *             @OA\Property(property="status", type="string", enum={"new", "draft", "moderation"}, description="new - мінімальний (default), draft - чернетка, moderation - на модерацію"),
     *             @OA\Property(property="local_id", type="string", maxLength=100, description="ID для синхронізації з клієнтом"),
     *             @OA\Property(property="user_type", type="string", enum={"personal", "legal"}, description="Тип автора (обов'язково для moderation)"),
     *             @OA\Property(property="title", type="object",
     *                 @OA\Property(property="uk", type="string", maxLength=255, description="Українською (обов'язково для moderation)"),
     *                 @OA\Property(property="en", type="string", maxLength=255, description="Англійською (опціонально)")
     *             ),
     *             @OA\Property(property="short_description", type="object",
     *                 @OA\Property(property="uk", type="string", maxLength=1000),
     *                 @OA\Property(property="en", type="string", maxLength=1000)
     *             ),
     *             @OA\Property(property="cover", type="string", description="Base64 зображення або URL"),
     *             @OA\Property(property="art_category", type="string", enum={"scenic", "visual", "fine_art", "literature", "music", "other"}, description="Обов'язково для moderation"),
     *             @OA\Property(property="art_subcategory", type="string", maxLength=100),
     *             @OA\Property(property="tags", type="object",
     *                 @OA\Property(property="uk", type="string", maxLength=500, description="Теги через кому"),
     *                 @OA\Property(property="en", type="string", maxLength=500)
     *             ),
     *             @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, description="Обов'язково для moderation"),
     *             @OA\Property(property="budget_goal", type="number", minimum=100, maximum=999999999, description="Обов'язково для moderation"),
     *             @OA\Property(property="estimated_days", type="integer", minimum=1, maximum=365),
     *             @OA\Property(property="budget_items", type="array", maxItems=50, @OA\Items(type="object",
     *                 @OA\Property(property="name", type="object", @OA\Property(property="uk", type="string"), @OA\Property(property="en", type="string")),
     *                 @OA\Property(property="amount", type="number", minimum=0)
     *             )),
     *             @OA\Property(property="additional_info", type="object",
     *                 @OA\Property(property="uk", type="string", maxLength=5000),
     *                 @OA\Property(property="en", type="string", maxLength=5000)
     *             ),
     *             @OA\Property(property="content_blocks", type="array", maxItems=50, @OA\Items(type="object",
     *                 @OA\Property(property="type", type="string", enum={"heading", "paragraph", "image"}),
     *                 @OA\Property(property="heading_level", type="string", enum={"h1", "h2", "h3", "h4", "h5", "h6"}),
     *                 @OA\Property(property="heading_text", type="object", @OA\Property(property="uk", type="string"), @OA\Property(property="en", type="string")),
     *                 @OA\Property(property="paragraph_text", type="object", @OA\Property(property="uk", type="string"), @OA\Property(property="en", type="string")),
     *                 @OA\Property(property="image", type="string"),
     *                 @OA\Property(property="image_alt", type="object", @OA\Property(property="uk", type="string"), @OA\Property(property="en", type="string"))
     *             )),
     *             @OA\Property(property="stages", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="title", type="object", @OA\Property(property="uk", type="string"), @OA\Property(property="en", type="string")),
     *                 @OA\Property(property="description", type="object", @OA\Property(property="uk", type="string"), @OA\Property(property="en", type="string")),
     *                 @OA\Property(property="days_planned", type="integer", minimum=1),
     *                 @OA\Property(property="budget_planned", type="number", minimum=0),
     *                 @OA\Property(property="order", type="integer", minimum=0)
     *             )),
     *             @OA\Property(property="bonuses", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="title", type="object", @OA\Property(property="uk", type="string"), @OA\Property(property="en", type="string")),
     *                 @OA\Property(property="description", type="object", @OA\Property(property="uk", type="string"), @OA\Property(property="en", type="string")),
     *                 @OA\Property(property="min_donation", type="number", minimum=1),
     *                 @OA\Property(property="max_donation", type="number", nullable=true, minimum=1),
     *                 @OA\Property(property="quantity", type="integer", nullable=true, minimum=1, description="null = необмежено"),
     *                 @OA\Property(property="order", type="integer", minimum=0)
     *             ))
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Проєкт створено або оновлено", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Project"))),
     *     @OA\Response(response=401, description="Не авторизований"),
     *     @OA\Response(response=422, description="Помилка валідації (для status=moderation)", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function store(StoreProjectRequest $request): ProjectResource
    {
        $data = $request->validated();
        $projectStatus = $request->getProjectStatus();

        // Проверяем, существует ли проект с таким local_id (для обновления черновиков)
        $project = null;
        if ($request->filled('local_id')) {
            $project = Project::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('status', [ProjectStatus::New, ProjectStatus::Draft])
                ->where('additional_info->local_id', $request->input('local_id'))
                ->first();
        }

        // Витягуємо stages, bonuses та parameters з даних
        $stagesData = $data['stages'] ?? [];
        $bonusesData = $data['bonuses'] ?? [];
        $parametersData = $data['parameters'] ?? null;
        unset($data['stages'], $data['bonuses'], $data['parameters']);

        if ($project) {
            // Обновляем существующий проект
            $this->updateExistingProject($project, $data, $projectStatus);
        } else {
            // Создаем новый проект
            $project = $this->createNewProject($data, $projectStatus, $request);
        }

        // Обновляем этапы и бонусы
        $this->updateProjectStagesAndBonuses($project, $stagesData, $bonusesData);

        // Оновлюємо характеристики (parameters), якщо передані
        ProjectCategoryParameterValues::syncForProject($project, $parametersData);

        return new ProjectResource($project->load(['user.profileLegal', 'stages', 'bonuses', 'projectParameters.parameter', 'projectParameters.parameterValue']));
    }

    /**
     * Создать новый проект
     */
    private function createNewProject(array $data, ProjectStatus $status, StoreProjectRequest $request): Project
    {
        // Розв'язуємо art_category + art_subcategory → art_category_id
        if (isset($data['art_category'])) {
            $data['art_category_id'] = ArtCategory::resolveIdFromSlugs(
                $data['art_category'] ?? null,
                $data['art_subcategory'] ?? null
            );
        }
        unset($data['art_category'], $data['art_subcategory']);

        // Генеруємо унікальний код та slug
        $data['code'] = strtoupper(Str::random(8));

        // Генеруємо slug з назви або випадковий для новых проектов
        if (isset($data['title']['uk'])) {
            $data['slug'] = Str::slug($data['title']['uk']).'-'.Str::random(6);
        } else {
            $titleText = $status === ProjectStatus::New ? 'Новий проект' : 'Чернетка';
            $data['slug'] = Str::slug($titleText).'-'.now()->format('dmY-Hi').'-'.Str::random(4);
            // Встановлюємо дефолтну назву, якщо не передана
            $data['title'] = $data['title'] ?? ['uk' => $titleText.' '.now()->format('d.m.Y H:i')];
        }

        // Обробка обкладинки (файл або Base64)
        if ($request->hasFile('cover')) {
            $data['cover'] = $request->file('cover')->store('projects/covers', 'public');
        } elseif (isset($data['cover'])) {
            $data['cover'] = $this->imageProcessor->processCover($data['cover']);
        }

        // Обробка зображень у content_blocks (Base64 → файли)
        if (isset($data['content_blocks'])) {
            $data['content_blocks'] = $this->imageProcessor->processContentBlocks($data['content_blocks']);
        }

        // Встановлюємо статуси
        $data['user_id'] = $request->user()->id;
        $data['status'] = $status;
        $data['status_moderation'] = ModerationStatus::Pending;
        $data['budget_collected'] = 0;
        $data['likes_count'] = 0;
        $data['donors_count'] = 0;

        // Зберігаємо local_id у additional_info для синхронізації з клієнтом
        if ($request->filled('local_id')) {
            $additionalInfo = is_array($data['additional_info'] ?? null) ? $data['additional_info'] : [];
            $additionalInfo['local_id'] = $data['local_id'];
            $data['additional_info'] = $additionalInfo;
        }
        unset($data['local_id']);

        return Project::create($data);
    }

    /**
     * Обновить существующий проект
     */
    private function updateExistingProject(Project $project, array $data, ProjectStatus $status): void
    {
        // Обновляем статус
        $project->status = $status;

        // Обновляем остальные поля, если они переданы
        foreach ($data as $key => $value) {
            if ($key === 'local_id') {
                continue;
            } // local_id не сохраняем в основных полях

            if ($value !== null) {
                if ($key === 'cover') {
                    $project->cover = $this->imageProcessor->processCover($value, $project->cover);
                } elseif ($key === 'content_blocks') {
                    $project->content_blocks = $this->imageProcessor->processContentBlocks(
                        $value,
                        $project->content_blocks
                    );
                } elseif ($key === 'art_category') {
                    $project->art_category_id = ArtCategory::resolveIdFromSlugs(
                        $data['art_category'] ?? null,
                        $data['art_subcategory'] ?? null
                    );
                } elseif ($key !== 'art_subcategory') {
                    $project->$key = $value;
                }
            }
        }

        if (isset($data['title']['uk'])) {
            $newSlug = $this->regenerateSlugFromTitle($project, $data['title']['uk']);
            if ($newSlug !== null) {
                $project->slug = $newSlug;
            }
        }

        $project->save();
    }

    /**
     * Якщо назва українською щойно вперше задана (а slug ще є автогенерованим placeholder'ом
     * на кшталт "novii-proekt-21072026-1836-fws5" чи "chernetka-..."), перегенеровує slug
     * на основі реальної назви. Після заміни placeholder'a на змістовний slug — далі не чіпаємо,
     * щоб не ламати вже роздані посилання на чернетку.
     */
    private function regenerateSlugFromTitle(Project $project, string $titleUk): ?string
    {
        if (trim($titleUk) === '') {
            return null;
        }

        if (! preg_match('/^(?:novii-proekt|chernetka)-\d{8}-\d{4}-[A-Za-z0-9]{4}$/', (string) $project->slug)) {
            return null;
        }

        do {
            $slug = Str::slug($titleUk).'-'.Str::random(6);
        } while (Project::where('slug', $slug)->where('id', '!=', $project->getKey())->exists());

        return $slug;
    }

    /**
     * Обновить этапы и бонусы проекта
     */
    private function updateProjectStagesAndBonuses(Project $project, array $stagesData, array $bonusesData): void
    {
        DB::transaction(function () use ($project, $stagesData, $bonusesData) {
            // Удаляем старые этапы и бонусы только если переданы новые
            if (! empty($stagesData)) {
                $project->stages()->delete();

                foreach ($stagesData as $index => $stageData) {
                    $project->stages()->create([
                        'title' => $stageData['title'] ?? ['uk' => 'Этап '.($index + 1)],
                        'description' => $stageData['description'] ?? null,
                        'days_planned' => $stageData['days_planned'] ?? null,
                        'budget_planned' => $stageData['budget_planned'] ?? 0,
                        'order' => $stageData['order'] ?? $index,
                        'status' => StageStatus::Planned,
                    ]);
                }
            }

            if (! empty($bonusesData)) {
                $project->bonuses()->delete();

                foreach ($bonusesData as $index => $bonusData) {
                    $project->bonuses()->create([
                        'title' => $bonusData['title'] ?? ['uk' => 'Бонус '.($index + 1)],
                        'description' => $bonusData['description'] ?? null,
                        'min_donation' => $bonusData['min_donation'] ?? 1,
                        'max_donation' => $bonusData['max_donation'] ?? null,
                        'quantity' => $bonusData['quantity'] ?? null,
                        'quantity_claimed' => 0,
                        'order' => $bonusData['order'] ?? $index,
                    ]);
                }
            }
        });
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
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="string"), example="cernetka-16022026-1245"),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
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

        return new ProjectResource($project->load(['user.profileLegal', 'stages', 'bonuses', 'projectParameters.parameter', 'projectParameters.parameterValue']));
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
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="string"), example="cernetka-16022026-1245"),
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
     *                 @OA\Property(property="estimated_days", type="integer"),
     *                 @OA\Property(property="content_blocks", type="string", description="JSON масив контент-блоків (до 50). Кожен блок має поле type: heading, paragraph або image.")
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
     *                 @OA\Property(property="estimated_days", type="integer"),
     *                 @OA\Property(property="content_blocks", type="array", description="Контент-блоки (до 50)", maxItems=50,
     *
     *                     @OA\Items(type="object",
     *                         required={"type"},
     *
     *                         @OA\Property(property="type", type="string", enum={"heading", "paragraph", "image"}),
     *                         @OA\Property(property="heading_level", type="string", enum={"h2", "h3", "h4", "h5", "h6"}),
     *                         @OA\Property(property="heading_text", ref="#/components/schemas/LocalizedString"),
     *                         @OA\Property(property="paragraph_text", ref="#/components/schemas/LocalizedString"),
     *                         @OA\Property(property="image", type="string"),
     *                         @OA\Property(property="image_alt", ref="#/components/schemas/LocalizedString"),
     *                         @OA\Property(property="image_caption", ref="#/components/schemas/LocalizedString")
     *                     )
     *                 )
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

        // Будь-яке редагування (в т.ч. проєкту, що стоїть у черзі на модерацію) повертає
        // проєкт у чернетку — щоб знову потрапити на модерацію, треба явно викликати submit.
        // Модерацію, що вже в обробці (status_moderation = processing), сюди не пускає
        // UpdateProjectRequest::authorize(), тож цей код виконується лише для pending.
        $data['status'] = ProjectStatus::Draft->value;

        // Витягуємо stages, bonuses та parameters з даних
        $stagesData = $data['stages'] ?? null;
        $bonusesData = $data['bonuses'] ?? null;
        $parametersData = $data['parameters'] ?? null;
        unset($data['stages'], $data['bonuses'], $data['parameters']);

        // Розв'язуємо art_category + art_subcategory → art_category_id (якщо передано)
        if (array_key_exists('art_category', $data)) {
            $data['art_category_id'] = ArtCategory::resolveIdFromSlugs(
                $data['art_category'] ?? null,
                $data['art_subcategory'] ?? null
            );
            unset($data['art_category'], $data['art_subcategory']);
        }

        // Обробка обкладинки (файл або Base64)
        if ($request->hasFile('cover')) {
            // Видаляємо стару обкладинку
            if ($project->cover) {
                Storage::disk('public')->delete($project->cover);
            }
            $data['cover'] = $request->file('cover')->store('projects/covers', 'public');
        } elseif (isset($data['cover'])) {
            $data['cover'] = $this->imageProcessor->processCover($data['cover'], $project->cover);
        }

        // Обробка зображень у content_blocks (Base64 → файли)
        if (isset($data['content_blocks'])) {
            $data['content_blocks'] = $this->imageProcessor->processContentBlocks(
                $data['content_blocks'],
                $project->content_blocks
            );
        }

        // Якщо назву щойно вперше задано — заміняємо автогенерований placeholder-slug на змістовний
        if (isset($data['title']['uk'])) {
            $newSlug = $this->regenerateSlugFromTitle($project, $data['title']['uk']);
            if ($newSlug !== null) {
                $data['slug'] = $newSlug;
            }
        }

        // Оновлюємо проєкт та зв'язані дані в транзакції
        DB::transaction(function () use ($project, $data, $stagesData, $bonusesData, $parametersData) {
            // Оновлюємо основні дані проєкту
            $project->update($data);

            // Оновлюємо характеристики (parameters), якщо передані
            ProjectCategoryParameterValues::syncForProject($project, $parametersData);

            // Оновлюємо етапи (якщо передано)
            if ($stagesData !== null) {
                $existingStageIds = $project->stages->pluck('id')->toArray();
                $receivedStageIds = [];

                foreach ($stagesData as $index => $stageData) {
                    if (! empty($stageData['id'])) {
                        // Оновлюємо існуючий етап
                        $stage = ProjectStage::where('id', $stageData['id'])
                            ->where('project_id', $project->id)
                            ->first();

                        if ($stage) {
                            $stage->update([
                                'title' => $stageData['title'],
                                'description' => $stageData['description'] ?? $stage->description,
                                'days_planned' => $stageData['days_planned'] ?? $stage->days_planned,
                                'budget_planned' => $stageData['budget_planned'] ?? $stage->budget_planned,
                                'order' => $stageData['order'] ?? $index,
                            ]);
                            $receivedStageIds[] = $stage->id;
                        }
                    } else {
                        // Створюємо новий етап
                        $newStage = $project->stages()->create([
                            'title' => $stageData['title'],
                            'description' => $stageData['description'] ?? null,
                            'days_planned' => $stageData['days_planned'] ?? null,
                            'budget_planned' => $stageData['budget_planned'] ?? 0,
                            'order' => $stageData['order'] ?? $index,
                            'status' => StageStatus::Planned,
                        ]);
                        $receivedStageIds[] = $newStage->id;
                    }
                }

                // Видаляємо етапи, яких немає в запиті
                $stagesToDelete = array_diff($existingStageIds, $receivedStageIds);
                ProjectStage::whereIn('id', $stagesToDelete)
                    ->where('project_id', $project->id)
                    ->delete();
            }

            // Оновлюємо бонуси (якщо передано)
            if ($bonusesData !== null) {
                $existingBonusIds = $project->bonuses->pluck('id')->toArray();
                $receivedBonusIds = [];

                foreach ($bonusesData as $index => $bonusData) {
                    if (! empty($bonusData['id'])) {
                        // Оновлюємо існуючий бонус
                        $bonus = ProjectBonus::where('id', $bonusData['id'])
                            ->where('project_id', $project->id)
                            ->first();

                        if ($bonus) {
                            $bonus->update([
                                'title' => $bonusData['title'],
                                'description' => $bonusData['description'] ?? $bonus->description,
                                'min_donation' => $bonusData['min_donation'],
                                'max_donation' => $bonusData['max_donation'] ?? $bonus->max_donation,
                                'quantity' => $bonusData['quantity'] ?? $bonus->quantity,
                                'order' => $bonusData['order'] ?? $index,
                            ]);
                            $receivedBonusIds[] = $bonus->id;
                        }
                    } else {
                        // Створюємо новий бонус
                        $newBonus = $project->bonuses()->create([
                            'title' => $bonusData['title'],
                            'description' => $bonusData['description'] ?? null,
                            'min_donation' => $bonusData['min_donation'],
                            'max_donation' => $bonusData['max_donation'] ?? null,
                            'quantity' => $bonusData['quantity'] ?? null,
                            'quantity_claimed' => 0,
                            'order' => $bonusData['order'] ?? $index,
                        ]);
                        $receivedBonusIds[] = $newBonus->id;
                    }
                }

                // Видаляємо бонуси, яких немає в запиті
                $bonusesToDelete = array_diff($existingBonusIds, $receivedBonusIds);
                ProjectBonus::whereIn('id', $bonusesToDelete)
                    ->where('project_id', $project->id)
                    ->delete();
            }
        });

        return new ProjectResource($project->fresh()->load(['user.profileLegal', 'stages', 'bonuses', 'projectParameters.parameter', 'projectParameters.parameterValue']));
    }

    /**
     * Часткове оновлення опублікованого проєкту (03.4.2.2)
     *
     * @OA\Patch(
     *     path="/v1/my/projects/{project}",
     *     operationId="updateMyProjectPartially",
     *     tags={"My Projects"},
     *     summary="Часткове оновлення опублікованого проєкту (03.4.2.2)",
     *     description="Дозволяє редагувати назву, опис, теги, обкладинку, контент-блоки, категорію, бюджет та характеристики (parameters) опублікованого проєкту. Для 'announced' бюджет можна лише збільшувати. Підтримує завантаження обкладинки через multipart/form-data.",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="string"), example="cernetka-16022026-1245"),
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
     *                 @OA\Property(property="additional_info[en]", type="string", description="Додаткова інформація англійською"),
     *                 @OA\Property(property="content_blocks", type="string", description="JSON масив контент-блоків (до 50). Кожен блок має поле type: heading, paragraph або image.")
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
     *                 @OA\Property(property="additional_info", ref="#/components/schemas/LocalizedString", description="Нова додаткова інформація"),
     *                 @OA\Property(property="content_blocks", type="array", description="Контент-блоки (до 50)", maxItems=50,
     *
     *                     @OA\Items(type="object",
     *                         required={"type"},
     *
     *                         @OA\Property(property="type", type="string", enum={"heading", "paragraph", "image"}),
     *                         @OA\Property(property="heading_level", type="string", enum={"h2", "h3", "h4", "h5", "h6"}),
     *                         @OA\Property(property="heading_text", ref="#/components/schemas/LocalizedString"),
     *                         @OA\Property(property="paragraph_text", ref="#/components/schemas/LocalizedString"),
     *                         @OA\Property(property="image", type="string"),
     *                         @OA\Property(property="image_alt", ref="#/components/schemas/LocalizedString"),
     *                         @OA\Property(property="image_caption", ref="#/components/schemas/LocalizedString")
     *                     )
     *                 ),
     *                 @OA\Property(property="art_category", type="string", description="Slug кореневої категорії"),
     *                 @OA\Property(property="art_subcategory", type="string", description="Slug підкатегорії"),
     *                 @OA\Property(property="budget_goal", type="number", description="Для 'announced' лише збільшення"),
     *                 @OA\Property(property="parameters", type="array", description="Характеристики проєкту (повний список — параметр, відсутній у масиві, буде видалено)",
     *
     *                     @OA\Items(type="object",
     *                         required={"parameter_id"},
     *
     *                         @OA\Property(property="parameter_id", type="integer", example=11),
     *                         @OA\Property(property="parameter_value_id", type="integer", nullable=true, description="Для type=list"),
     *                         @OA\Property(property="custom_value", ref="#/components/schemas/LocalizedString", description="Для type=custom")
     *                     )
     *                 )
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

        // Витягуємо parameters з даних
        $parametersData = $data['parameters'] ?? null;
        unset($data['parameters']);

        // Розв'язуємо art_category + art_subcategory → art_category_id (якщо передано)
        if (array_key_exists('art_category', $data)) {
            $data['art_category_id'] = ArtCategory::resolveIdFromSlugs(
                $data['art_category'] ?? null,
                $data['art_subcategory'] ?? null
            );
            unset($data['art_category'], $data['art_subcategory']);
        }

        // Обробка обкладинки (файл або Base64)
        if ($request->hasFile('cover')) {
            if ($project->cover) {
                Storage::disk('public')->delete($project->cover);
            }
            $data['cover'] = $request->file('cover')->store('projects/covers', 'public');
        } elseif (isset($data['cover'])) {
            $data['cover'] = $this->imageProcessor->processCover($data['cover'], $project->cover);
        }

        // Обробка зображень у content_blocks (Base64 → файли)
        if (isset($data['content_blocks'])) {
            $data['content_blocks'] = $this->imageProcessor->processContentBlocks(
                $data['content_blocks'],
                $project->content_blocks
            );
        }

        $project->update($data);

        // Оновлюємо характеристики (parameters), якщо передані
        ProjectCategoryParameterValues::syncForProject($project, $parametersData);

        return new ProjectResource($project->fresh()->load(['user.profileLegal', 'stages', 'bonuses', 'projectParameters.parameter', 'projectParameters.parameterValue']));
    }

    /**
     * Видалити проєкт (чернетки, відхилені, в черзі на модерацію)
     *
     * @OA\Delete(
     *     path="/v1/my/projects/{project}",
     *     operationId="deleteMyProject",
     *     tags={"My Projects"},
     *     summary="Видалити проєкт",
     *     description="Видаляє проєкт. Доступно для чернеток, відхилених проєктів та проєктів у черзі на модерацію (поки їх не взяв в обробку модератор). Опубліковані проєкти видаляються лише через модераторів.",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="string"), example="cernetka-16022026-1245"),
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
     *     @OA\Response(response=422, description="Неможливо видалити (проєкт опублікований або на модерації в обробці)")
     * )
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! $project->canBeDeletedByOwner()) {
            return response()->json([
                'message' => 'Можна видалити лише чернетки, відхилені проєкти або проєкти в черзі на модерацію. Для інших проєктів зверніться до модератора.',
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
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="string"), example="cernetka-16022026-1245"),
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

        if (! in_array($project->status, [ProjectStatus::Draft, ProjectStatus::Rejected, ProjectStatus::New, ProjectStatus::Moderation])) {
            \Log::info('Спроба відправити на модерацію проєкт з недопустимим статусом', [
                'project_id' => $project->id,
                'current_status' => $project->status,
            ]);

            return response()->json([
                'message' => 'На модерацію можна відправити лише чернетку або відхилений проєкт.',
            ], 422);
        }

        // Валідація обов'язкових полів перед відправкою
        $errors = [];
        if (empty($project->title['uk'])) {
            $errors['title'] = ['Назва проєкту є обов\'язковою'];
        }
        if (empty($project->art_category_id)) {
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

        // Используем workflow service для отправки на модерацию
        $success = $this->workflowService->submitForModeration($project);

        if (! $success) {
            return response()->json([
                'message' => 'Неможливо відправити проєкт на модерацію.',
            ], 422);
        }

        return response()->json([
            'message' => 'Проєкт відправлено на модерацію.',
            'data' => new ProjectResource($project->load(['user.profileLegal', 'stages', 'bonuses', 'projectParameters.parameter', 'projectParameters.parameterValue'])),
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
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="string"), example="cernetka-16022026-1245"),
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

        // Нормалізація: multipart з кількома "files" (без []) дає один файл; curl -F files=@a -F files=@b теж може дати один або масив — завжди приводимо до масиву
        $rawFiles = $request->file('files');
        $files = $rawFiles === null ? [] : (is_array($rawFiles) ? $rawFiles : [$rawFiles]);

        $rules = [
            'type' => ['required', 'in:image,gallery,video,document'],
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'max:51200'], // 50MB max
            'description' => ['nullable', 'array'],
            'description.uk' => ['nullable', 'string', 'max:2000'],
            'description.en' => ['nullable', 'string', 'max:2000'],
        ];
        $messages = [
            'files.required' => 'Завантажте хоча б один файл',
            'files.min' => 'Завантажте хоча б один файл',
            'files.max' => 'Максимум 10 файлів',
            'files.*.max' => 'Максимальний розмір файлу 50MB',
        ];
        Validator::make(array_merge($request->all(), ['files' => $files]), $rules, $messages)->validate();

        $type = $request->input('type');

        // Додаткова валідація типів файлів залежно від type
        $allowedMimes = match ($type) {
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'gallery' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'video' => ['mp4', 'mov', 'avi', 'webm', 'mkv'],
            'document' => ['pdf', 'doc', 'docx'],
        };

        foreach ($files as $file) {
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
        foreach ($files as $file) {
            $path = $file->store("projects/{$project->id}/final-result", 'public');
            $uploadedFiles[] = [
                'path' => $path,
                // Зберігаємо відносний шлях, без домену. Домен додається на рівні API/клієнта.
                'url' => '/storage/'.$path,
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
            'data' => new ProjectResource($project->fresh()->load(['user.profileLegal', 'stages', 'bonuses', 'projectParameters.parameter', 'projectParameters.parameterValue'])),
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
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="string"), example="cernetka-16022026-1245"),
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
            'data' => new ProjectResource($project->load(['user.profileLegal', 'stages', 'bonuses', 'projectParameters.parameter', 'projectParameters.parameterValue'])),
        ]);
    }
}
