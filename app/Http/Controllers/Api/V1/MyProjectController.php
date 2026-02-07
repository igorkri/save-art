<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Enums\StageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateProjectRequest;
use App\Http\Requests\Api\V1\UpdateProjectRequest;
use App\Http\Requests\Api\V1\UpdatePublishedProjectRequest;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
use App\Models\ProjectBonus;
use App\Models\ProjectStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
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
     *     security={{"sanctum":{}, "apiKey":{}}},
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
            ->with(['user.profileLegal'])
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
     *     summary="Створити проєкт з етапами та бонусами",
     *     description="Створює новий проєкт у статусі чернетки разом з етапами реалізації та бонусами для меценатів.",
     *     security={{"sanctum":{}, "apiKey":{}}},
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
     *                 @OA\Property(property="title[uk]", type="string", example="Картина 'Світанок над Дніпром'", description="Назва українською (обов'язкова)"),
     *                 @OA\Property(property="title[en]", type="string", example="Painting 'Dawn over Dnipro'", description="Назва англійською"),
     *                 @OA\Property(property="short_description[uk]", type="string", example="Олійний живопис на полотні, присвячений красі українських пейзажів"),
     *                 @OA\Property(property="short_description[en]", type="string", example="Oil painting on canvas dedicated to the beauty of Ukrainian landscapes"),
     *                 @OA\Property(property="cover", type="string", format="binary", description="Обкладинка проєкту (JPG, PNG, до 15MB)"),
     *                 @OA\Property(property="art_category", type="string", enum={"scenic", "visual", "fine_art", "literature", "music", "other"}, example="fine_art"),
     *                 @OA\Property(property="art_subcategory", type="string", nullable=true, example="painting"),
     *                 @OA\Property(property="tags[uk]", type="string", example="живопис, пейзаж, Україна, олія"),
     *                 @OA\Property(property="tags[en]", type="string", example="painting, landscape, Ukraine, oil"),
     *                 @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, example="UAH"),
     *                 @OA\Property(property="budget_goal", type="number", format="float", minimum=100, example=75000),
     *                 @OA\Property(property="estimated_days", type="integer", minimum=1, maximum=365, example=90),
     *                 @OA\Property(property="content_blocks", type="string", description="JSON масив контент-блоків (до 50). Кожен блок має поле type: heading, paragraph або image. Приклад: [{""type"":""heading"",""heading_level"":""h2"",""heading_text"":{""uk"":""Заголовок""}}]")
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 required={"title", "user_type", "art_category", "currency", "budget_goal"},
     *                 example={
     *                     "user_type": "personal",
     *                     "title": {"uk": "Картина 'Світанок над Дніпром'", "en": "Painting 'Dawn over Dnipro'"},
     *                     "short_description": {"uk": "Олійний живопис на полотні, присвячений красі українських пейзажів", "en": "Oil painting on canvas dedicated to the beauty of Ukrainian landscapes"},
     *                     "art_category": "fine_art",
     *                     "art_subcategory": "painting",
     *                     "tags": {"uk": "живопис, пейзаж, Україна", "en": "painting, landscape, Ukraine"},
     *                     "currency": "UAH",
     *                     "budget_goal": 75000,
     *                     "estimated_days": 90,
     *                     "budget_items": {
     *                         {"name": {"uk": "Фарби та матеріали", "en": "Paints and materials"}, "amount": 15000},
     *                         {"name": {"uk": "Полотно та підрамник", "en": "Canvas and stretcher"}, "amount": 8000},
     *                         {"name": {"uk": "Рама для картини", "en": "Picture frame"}, "amount": 12000},
     *                         {"name": {"uk": "Оренда студії", "en": "Studio rent"}, "amount": 20000},
     *                         {"name": {"uk": "Доставка та пакування", "en": "Delivery and packaging"}, "amount": 5000},
     *                         {"name": {"uk": "Комісія платформи", "en": "Platform commission"}, "amount": 15000}
     *                     },
     *                     "characteristics": {
     *                         {"name": {"uk": "Розмір", "en": "Size"}, "value": {"uk": "100x150 см", "en": "100x150 cm"}},
     *                         {"name": {"uk": "Техніка", "en": "Technique"}, "value": {"uk": "Олія на полотні", "en": "Oil on canvas"}},
     *                         {"name": {"uk": "Рік створення", "en": "Year"}, "value": {"uk": "2024", "en": "2024"}}
     *                     },
     *                     "additional_info": {"uk": "Картина буде готова протягом 3 місяців. Доставка по Україні безкоштовна.", "en": "The painting will be ready within 3 months. Free delivery within Ukraine."},
     *                     "content_blocks": {
     *                         {"type": "heading", "heading_level": "h2", "heading_text": {"uk": "Про проєкт", "en": "About the project"}},
     *                         {"type": "paragraph", "paragraph_text": {"uk": "Опис проєкту...", "en": "Project description..."}},
     *                         {"type": "image", "image": "projects/content/img.jpg", "image_alt": {"uk": "Опис", "en": "Description"}}
     *                     },
     *                     "stages": {
     *                         {"title": {"uk": "Підготовка ескізів", "en": "Sketch preparation"}, "description": {"uk": "Створення детальних ескізів та вибір композиції", "en": "Creating detailed sketches and choosing the composition"}, "days_planned": 14, "budget_planned": 5000},
     *                         {"title": {"uk": "Підготовка полотна", "en": "Canvas preparation"}, "description": {"uk": "Натягування полотна, ґрунтування", "en": "Stretching canvas, priming"}, "days_planned": 7, "budget_planned": 8000},
     *                         {"title": {"uk": "Основна робота", "en": "Main work"}, "description": {"uk": "Нанесення фарб, робота над деталями", "en": "Applying paints, working on details"}, "days_planned": 45, "budget_planned": 35000},
     *                         {"title": {"uk": "Завершення та оформлення", "en": "Finishing and framing"}, "description": {"uk": "Лакування, оформлення в раму", "en": "Varnishing, framing"}, "days_planned": 14, "budget_planned": 17000},
     *                         {"title": {"uk": "Доставка", "en": "Delivery"}, "description": {"uk": "Пакування та відправка покупцю", "en": "Packaging and shipping to buyer"}, "days_planned": 10, "budget_planned": 10000}
     *                     },
     *                     "bonuses": {
     *                         {"title": {"uk": "Подяка на сайті", "en": "Thanks on website"}, "description": {"uk": "Ваше ім'я буде вказано у списку меценатів проєкту", "en": "Your name will be listed among project patrons"}, "min_donation": 100, "quantity": null},
     *                         {"title": {"uk": "Листівка з репродукцією", "en": "Postcard with reproduction"}, "description": {"uk": "Авторська листівка з репродукцією картини", "en": "Author's postcard with painting reproduction"}, "min_donation": 500, "quantity": 100},
     *                         {"title": {"uk": "Підписаний принт А4", "en": "Signed A4 print"}, "description": {"uk": "Якісний принт картини з підписом автора", "en": "Quality print of the painting with author's signature"}, "min_donation": 1500, "quantity": 50},
     *                         {"title": {"uk": "Підписаний принт А3", "en": "Signed A3 print"}, "description": {"uk": "Великий принт картини з підписом та сертифікатом", "en": "Large print with signature and certificate"}, "min_donation": 3000, "quantity": 25},
     *                         {"title": {"uk": "Відвідування студії", "en": "Studio visit"}, "description": {"uk": "Особиста екскурсія до студії та спостереження за процесом створення", "en": "Personal studio tour and watching the creation process"}, "min_donation": 5000, "quantity": 10},
     *                         {"title": {"uk": "Оригінальний ескіз", "en": "Original sketch"}, "description": {"uk": "Один з оригінальних ескізів до картини з підписом", "en": "One of the original sketches with signature"}, "min_donation": 10000, "quantity": 5}
     *                     }
     *                 },
     *
     *                 @OA\Property(property="user_type", type="string", enum={"personal", "legal"}, description="Тип автора"),
     *                 @OA\Property(property="title", type="object", description="Назва проєкту",
     *                     @OA\Property(property="uk", type="string", description="Українською (обов'язково)"),
     *                     @OA\Property(property="en", type="string", description="Англійською")
     *                 ),
     *                 @OA\Property(property="short_description", type="object", description="Короткий опис",
     *                     @OA\Property(property="uk", type="string", maxLength=1000),
     *                     @OA\Property(property="en", type="string", maxLength=1000)
     *                 ),
     *                 @OA\Property(property="art_category", type="string", enum={"scenic", "visual", "fine_art", "literature", "music", "other"}, description="Галузь мистецтва"),
     *                 @OA\Property(property="art_subcategory", type="string", nullable=true, description="Підкатегорія"),
     *                 @OA\Property(property="tags", type="object", description="Теги через кому",
     *                     @OA\Property(property="uk", type="string"),
     *                     @OA\Property(property="en", type="string")
     *                 ),
     *                 @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, description="Валюта"),
     *                 @OA\Property(property="budget_goal", type="number", minimum=100, description="Ціль збору"),
     *                 @OA\Property(property="estimated_days", type="integer", minimum=1, maximum=365, description="Термін реалізації (днів)"),
     *                 @OA\Property(property="budget_items", type="array", description="Статті бюджету",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="name", type="object",
     *                             @OA\Property(property="uk", type="string"),
     *                             @OA\Property(property="en", type="string")
     *                         ),
     *                         @OA\Property(property="amount", type="number", description="Сума")
     *                     )
     *                 ),
     *                 @OA\Property(property="characteristics", type="array", description="Характеристики проєкту",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="name", type="object",
     *                             @OA\Property(property="uk", type="string"),
     *                             @OA\Property(property="en", type="string")
     *                         ),
     *                         @OA\Property(property="value", type="object",
     *                             @OA\Property(property="uk", type="string"),
     *                             @OA\Property(property="en", type="string")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="additional_info", type="object", description="Додаткова інформація",
     *                     @OA\Property(property="uk", type="string", maxLength=10000),
     *                     @OA\Property(property="en", type="string", maxLength=10000)
     *                 ),
     *                 @OA\Property(property="content_blocks", type="array", description="Контент-блоки проєкту (до 50)", maxItems=50,
     *
     *                     @OA\Items(type="object",
     *                         required={"type"},
     *
     *                         @OA\Property(property="type", type="string", enum={"heading", "paragraph", "image"}, description="Тип блоку"),
     *                         @OA\Property(property="heading_level", type="string", enum={"h2", "h3", "h4", "h5", "h6"}, description="Рівень заголовка (для type=heading)"),
     *                         @OA\Property(property="heading_text", type="object", description="Текст заголовка (для type=heading)",
     *                             @OA\Property(property="uk", type="string", maxLength=255),
     *                             @OA\Property(property="en", type="string", maxLength=255)
     *                         ),
     *                         @OA\Property(property="paragraph_text", type="object", description="Текст параграфа (для type=paragraph)",
     *                             @OA\Property(property="uk", type="string", maxLength=10000),
     *                             @OA\Property(property="en", type="string", maxLength=10000)
     *                         ),
     *                         @OA\Property(property="image", type="string", maxLength=500, description="Шлях до зображення (для type=image)"),
     *                         @OA\Property(property="image_alt", type="object", description="Alt текст (для type=image)",
     *                             @OA\Property(property="uk", type="string", maxLength=255),
     *                             @OA\Property(property="en", type="string", maxLength=255)
     *                         ),
     *                         @OA\Property(property="image_caption", type="object", description="Підпис (для type=image)",
     *                             @OA\Property(property="uk", type="string", maxLength=500),
     *                             @OA\Property(property="en", type="string", maxLength=500)
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="stages", type="array", description="Етапи реалізації (до 20)",
     *
     *                     @OA\Items(type="object",
     *                         required={"title"},
     *
     *                         @OA\Property(property="title", type="object", required={"uk"},
     *                             @OA\Property(property="uk", type="string", description="Назва українською (обов'язково)"),
     *                             @OA\Property(property="en", type="string")
     *                         ),
     *                         @OA\Property(property="description", type="object",
     *                             @OA\Property(property="uk", type="string", maxLength=2000),
     *                             @OA\Property(property="en", type="string", maxLength=2000)
     *                         ),
     *                         @OA\Property(property="days_planned", type="integer", minimum=1, description="Планова тривалість (днів)"),
     *                         @OA\Property(property="budget_planned", type="number", minimum=0, description="Плановий бюджет"),
     *                         @OA\Property(property="order", type="integer", minimum=0, description="Порядок (автоматично якщо не вказано)")
     *                     )
     *                 ),
     *                 @OA\Property(property="bonuses", type="array", description="Бонуси для меценатів (до 20)",
     *
     *                     @OA\Items(type="object",
     *                         required={"title", "min_donation"},
     *
     *                         @OA\Property(property="title", type="object", required={"uk"},
     *                             @OA\Property(property="uk", type="string", description="Назва українською (обов'язково)"),
     *                             @OA\Property(property="en", type="string")
     *                         ),
     *                         @OA\Property(property="description", type="object",
     *                             @OA\Property(property="uk", type="string", maxLength=2000),
     *                             @OA\Property(property="en", type="string", maxLength=2000)
     *                         ),
     *                         @OA\Property(property="min_donation", type="number", minimum=10, description="Мінімальна сума підтримки"),
     *                         @OA\Property(property="quantity", type="integer", nullable=true, minimum=1, description="Кількість (null = необмежено)"),
     *                         @OA\Property(property="order", type="integer", minimum=0, description="Порядок (автоматично якщо не вказано)")
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

        // Витягуємо stages та bonuses з даних
        $stagesData = $data['stages'] ?? [];
        $bonusesData = $data['bonuses'] ?? [];
        unset($data['stages'], $data['bonuses']);

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

        // Створюємо проєкт та зв'язані дані в транзакції
        $project = DB::transaction(function () use ($data, $stagesData, $bonusesData) {
            $project = Project::create($data);

            // Створюємо етапи
            foreach ($stagesData as $index => $stageData) {
                $project->stages()->create([
                    'title' => $stageData['title'],
                    'description' => $stageData['description'] ?? null,
                    'days_planned' => $stageData['days_planned'] ?? null,
                    'budget_planned' => $stageData['budget_planned'] ?? 0,
                    'order' => $stageData['order'] ?? $index,
                    'status' => StageStatus::Planned,
                ]);
            }

            // Створюємо бонуси
            foreach ($bonusesData as $index => $bonusData) {
                $project->bonuses()->create([
                    'title' => $bonusData['title'],
                    'description' => $bonusData['description'] ?? null,
                    'min_donation' => $bonusData['min_donation'],
                    'quantity' => $bonusData['quantity'] ?? null,
                    'quantity_claimed' => 0,
                    'order' => $bonusData['order'] ?? $index,
                ]);
            }

            return $project;
        });

        return new ProjectResource($project->load(['user.profileLegal', 'stages', 'bonuses']));
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
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer"), example=1),
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

        return new ProjectResource($project->load(['user.profileLegal', 'stages', 'bonuses']));
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

        // Витягуємо stages та bonuses з даних
        $stagesData = $data['stages'] ?? null;
        $bonusesData = $data['bonuses'] ?? null;
        unset($data['stages'], $data['bonuses']);

        // Обробка обкладинки
        if ($request->hasFile('cover')) {
            // Видаляємо стару обкладинку
            if ($project->cover) {
                Storage::disk('public')->delete($project->cover);
            }
            $data['cover'] = $request->file('cover')->store('projects/covers', 'public');
        }

        // Оновлюємо проєкт та зв'язані дані в транзакції
        DB::transaction(function () use ($project, $data, $stagesData, $bonusesData) {
            // Оновлюємо основні дані проєкту
            $project->update($data);

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

        return new ProjectResource($project->fresh()->load(['user.profileLegal', 'stages', 'bonuses']));
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
     *     security={{"sanctum":{}, "apiKey":{}}},
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

        // Обробка обкладинки
        if ($request->hasFile('cover')) {
            if ($project->cover) {
                Storage::disk('public')->delete($project->cover);
            }
            $data['cover'] = $request->file('cover')->store('projects/covers', 'public');
        }

        $project->update($data);

        return new ProjectResource($project->load(['user.profileLegal', 'stages', 'bonuses']));
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
     *     security={{"sanctum":{}, "apiKey":{}}},
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
     *     security={{"sanctum":{}, "apiKey":{}}},
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
            'data' => new ProjectResource($project->load(['user.profileLegal', 'stages', 'bonuses'])),
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
            'data' => new ProjectResource($project->fresh()->load(['user.profileLegal', 'stages', 'bonuses'])),
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
            'data' => new ProjectResource($project->load(['user.profileLegal', 'stages', 'bonuses'])),
        ]);
    }
}
