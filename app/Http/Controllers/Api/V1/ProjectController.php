<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ArtCategory;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;

class ProjectController extends Controller
{
    /**
     * Отримати список публічних проєктів з фільтрацією та пагінацією
     *
     * @OA\Get(
     *     path="/v1/projects",
     *     operationId="getProjects",
     *     tags={"Projects"},
     *     summary="Список публічних проектів з опціями фільтрації",
     *     description="Повертає список публічних проектів з можливістю фільтрації та пагінації, а також всі доступні опції фільтрації. Параметри art_category, art_subcategory, status, currency та tags підтримують множинні значення через кому.",
     *
     *     @OA\Parameter(name="language", in="query", description="Мова для назв фільтрів (uk, en). Якщо вказано - повертає строку замість об'єкта з мовами", @OA\Schema(type="string", enum={"uk", "en"}), example="uk"),
     *     @OA\Parameter(name="art_category", in="query", description="Фільтр по категорії (можна вказати кілька через кому)", @OA\Schema(type="string"), example="visual,literature"),
     *     @OA\Parameter(name="art_subcategory", in="query", description="Фільтр по підкатегорії (можна вказати кілька через кому)", @OA\Schema(type="string"), example="poetry,prose,photography,painting"),
     *     @OA\Parameter(name="status", in="query", description="Фільтр по статусу (можна вказати кілька через кому)", @OA\Schema(type="string"), example="announced,in_progress,completed"),
     *     @OA\Parameter(name="currency", in="query", description="Фільтр по валюті (можна вказати кілька через кому)", @OA\Schema(type="string"), example="UAH,USD,EUR"),
     *     @OA\Parameter(name="budget_min", in="query", description="Мінімальний бюджет", @OA\Schema(type="number"), example=1000),
     *     @OA\Parameter(name="budget_max", in="query", description="Максимальний бюджет", @OA\Schema(type="number"), example=100000),
     *     @OA\Parameter(name="budget_collected_min", in="query", description="Мінімальна зібрана сума", @OA\Schema(type="number")),
     *     @OA\Parameter(name="budget_collected_max", in="query", description="Максимальна зібрана сума", @OA\Schema(type="number")),
     *     @OA\Parameter(name="progress_min", in="query", description="Мінімальний прогрес збору (відсотки 0-100)", @OA\Schema(type="number", minimum=0, maximum=100)),
     *     @OA\Parameter(name="progress_max", in="query", description="Максимальний прогрес збору (відсотки 0-100)", @OA\Schema(type="number", minimum=0, maximum=100)),
     *     @OA\Parameter(name="days_left_min", in="query", description="Мінімум днів до завершення", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="days_left_max", in="query", description="Максимум днів до завершення", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="tags", in="query", description="Фільтр по тегах (можна вказати кілька через кому)", @OA\Schema(type="string"), example="живопис,сучасне мистецтво"),
     *     @OA\Parameter(name="user_id", in="query", description="Фільтр по автору (ID користувача)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="announced_from", in="query", description="Оголошені від дати (YYYY-MM-DD)", @OA\Schema(type="string", format="date"), example="2025-01-01"),
     *     @OA\Parameter(name="announced_to", in="query", description="Оголошені до дати (YYYY-MM-DD)", @OA\Schema(type="string", format="date"), example="2025-12-31"),
     *     @OA\Parameter(name="search", in="query", description="Пошук по назві та опису", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort_by", in="query", description="Сортування", @OA\Schema(type="string", enum={"newest", "popular", "ending_soon", "most_funded"}), example="newest"),
     *     @OA\Parameter(name="sort_dir", in="query", description="Напрямок сортування", @OA\Schema(type="string", enum={"asc", "desc"}), example="desc"),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс 50)", @OA\Schema(type="integer", default=15, minimum=1, maximum=50)),
     *     @OA\Parameter(name="page", in="query", description="Номер сторінки", @OA\Schema(type="integer", minimum=1)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список проектів з опціями фільтрації",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProjectList")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="filters", type="object", description="Доступні опції фільтрації"),
     *             @OA\Property(property="filters_applied", type="object", description="Застосовані фільтри")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Отримуємо мову для фільтрів
        $language = $request->query('language');
        $supportedLanguages = ['uk', 'en'];
        if ($language && ! in_array($language, $supportedLanguages)) {
            $language = 'uk';
        }

        // Отримуємо опції фільтрації з кешу
        $cacheKey = $language ? "project_filters_{$language}" : 'project_filters';
        $filters = Cache::remember($cacheKey, 300, function () use ($language) {
            return [
                'categories' => $this->getFilterCategories($language),
                'statuses' => $this->getFilterStatuses($language),
                'budget_range' => $this->getFilterBudgetRange(),
                'currencies' => $this->getFilterCurrencies($language),
                'sort_options' => $this->getFilterSortOptions($language),
                'total_projects' => $this->getFilterTotalProjects(),
            ];
        });

        // Будуємо запит для проєктів
        $query = Project::query()
            ->with(['user.profilePersonal', 'user.profileLegal'])
            ->whereIn('status', ProjectStatus::publicStatuses());

        // Фільтр по категорії (підтримує множинні значення через кому)
        if ($request->filled('art_category')) {
            $categories = array_map('trim', explode(',', $request->input('art_category')));
            $query->whereIn('art_category', $categories);
        }

        // Фільтр по підкатегорії (підтримує множинні значення через кому)
        if ($request->filled('art_subcategory')) {
            $subcategories = array_map('trim', explode(',', $request->input('art_subcategory')));
            $query->whereIn('art_subcategory', $subcategories);
        }

        // Фільтр по статусу (підтримує множинні значення через кому)
        if ($request->filled('status')) {
            $statuses = array_map('trim', explode(',', $request->input('status')));
            $publicStatuses = array_map(fn ($s) => $s->value, ProjectStatus::publicStatuses());
            $validStatuses = array_intersect($statuses, $publicStatuses);
            if (! empty($validStatuses)) {
                $query->whereIn('status', $validStatuses);
            }
        }

        // Фільтр по валюті (підтримує множинні значення через кому)
        if ($request->filled('currency')) {
            $currencies = array_map('trim', explode(',', $request->input('currency')));
            $query->whereIn('currency', $currencies);
        }

        // Фільтр по цільовому бюджету
        if ($request->filled('budget_min')) {
            $query->where('budget_goal', '>=', $request->input('budget_min'));
        }
        if ($request->filled('budget_max')) {
            $query->where('budget_goal', '<=', $request->input('budget_max'));
        }

        // Фільтр по зібраному бюджету
        if ($request->filled('budget_collected_min')) {
            $query->where('budget_collected', '>=', $request->input('budget_collected_min'));
        }
        if ($request->filled('budget_collected_max')) {
            $query->where('budget_collected', '<=', $request->input('budget_collected_max'));
        }

        // Фільтр по прогресу збору коштів (відсотки)
        if ($request->filled('progress_min') || $request->filled('progress_max')) {
            $progressMin = $request->input('progress_min', 0);
            $progressMax = $request->input('progress_max', 100);
            $query->whereRaw('(budget_collected / budget_goal * 100) >= ?', [$progressMin])
                ->whereRaw('(budget_collected / budget_goal * 100) <= ?', [$progressMax]);
        }

        // Фільтр по тегах (множинні значення)
        if ($request->filled('tags')) {
            $tags = array_map('trim', explode(',', $request->input('tags')));
            foreach ($tags as $tag) {
                $query->whereRaw('JSON_CONTAINS(tags, ?)', [json_encode($tag)]);
            }
        }

        // Фільтр по автору
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Фільтр по даті оголошення
        if ($request->filled('announced_from')) {
            $query->whereDate('announced_at', '>=', $request->input('announced_from'));
        }
        if ($request->filled('announced_to')) {
            $query->whereDate('announced_at', '<=', $request->input('announced_to'));
        }

        // Фільтр по кількості днів до завершення
        if ($request->filled('days_left_min') || $request->filled('days_left_max')) {
            $now = now();
            if ($request->filled('days_left_min')) {
                $minDate = $now->copy()->addDays($request->input('days_left_min'));
                $query->where('planned_completion_at', '>=', $minDate);
            }
            if ($request->filled('days_left_max')) {
                $maxDate = $now->copy()->addDays($request->input('days_left_max'));
                $query->where('planned_completion_at', '<=', $maxDate);
            }
        }

        // Пошук по назві та опису
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw("JSON_EXTRACT(title, '$.uk') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_EXTRACT(title, '$.en') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_EXTRACT(short_description, '$.uk') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_EXTRACT(short_description, '$.en') LIKE ?", ["%{$search}%"]);
            });
        }

        // Сортування (конвертуємо slug в поле БД)
        $sortBy = $request->input('sort_by', 'newest');
        $sortDir = $request->input('sort_dir', 'desc');
        $sortMapping = [
            'newest' => 'announced_at',
            'popular' => 'likes_count',
            'ending_soon' => 'planned_completion_at',
            'most_funded' => 'budget_collected',
            // Старі значення для сумісності
            'announced_at' => 'announced_at',
            'budget_goal' => 'budget_goal',
            'budget_collected' => 'budget_collected',
            'likes_count' => 'likes_count',
            'donors_count' => 'donors_count',
            'created_at' => 'created_at',
            'planned_completion_at' => 'planned_completion_at',
        ];

        $sortField = $sortMapping[$sortBy] ?? 'announced_at';

        // Для ending_soon сортуємо за зростанням (найближчі спочатку)
        if ($sortBy === 'ending_soon') {
            $query->orderBy($sortField, 'asc');
        } else {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min(max(1, $request->input('per_page', 15)), 50);
        $projects = $query->paginate($perPage);

        // Створюємо колекцію ресурсів і додаємо фільтри через additional()
        $collection = ProjectListResource::collection($projects);

        $additionalData = [
            'filters' => $filters,
            'filters_applied' => $this->getAppliedFilters($request),
        ];

        if ($language) {
            $additionalData['language'] = $language;
        }

        return $collection->additional($additionalData);
    }

    /**
     * Отримати застосовані фільтри з запиту
     */
    private function getAppliedFilters(Request $request): array
    {
        $filters = [];

        if ($request->filled('art_category')) {
            $filters['art_category'] = array_map('trim', explode(',', $request->input('art_category')));
        }

        if ($request->filled('art_subcategory')) {
            $filters['art_subcategory'] = array_map('trim', explode(',', $request->input('art_subcategory')));
        }

        if ($request->filled('status')) {
            $filters['status'] = array_map('trim', explode(',', $request->input('status')));
        }

        if ($request->filled('currency')) {
            $filters['currency'] = array_map('trim', explode(',', $request->input('currency')));
        }

        if ($request->filled('budget_min')) {
            $filters['budget_min'] = (float) $request->input('budget_min');
        }

        if ($request->filled('budget_max')) {
            $filters['budget_max'] = (float) $request->input('budget_max');
        }

        if ($request->filled('budget_collected_min')) {
            $filters['budget_collected_min'] = (float) $request->input('budget_collected_min');
        }

        if ($request->filled('budget_collected_max')) {
            $filters['budget_collected_max'] = (float) $request->input('budget_collected_max');
        }

        if ($request->filled('progress_min')) {
            $filters['progress_min'] = (float) $request->input('progress_min');
        }

        if ($request->filled('progress_max')) {
            $filters['progress_max'] = (float) $request->input('progress_max');
        }

        if ($request->filled('tags')) {
            $filters['tags'] = array_map('trim', explode(',', $request->input('tags')));
        }

        if ($request->filled('user_id')) {
            $filters['user_id'] = (int) $request->input('user_id');
        }

        if ($request->filled('announced_from')) {
            $filters['announced_from'] = $request->input('announced_from');
        }

        if ($request->filled('announced_to')) {
            $filters['announced_to'] = $request->input('announced_to');
        }

        if ($request->filled('search')) {
            $filters['search'] = $request->input('search');
        }

        if ($request->filled('sort_by')) {
            $filters['sort_by'] = $request->input('sort_by');
        }

        if ($request->filled('sort_dir')) {
            $filters['sort_dir'] = $request->input('sort_dir');
        }

        return $filters;
    }

    /**
     * Отримати деталі проєкту за slug
     *
     * @OA\Get(
     *     path="/v1/projects/{slug}",
     *     operationId="getProject",
     *     tags={"Projects"},
     *     summary="Деталі проекту",
     *     description="Повертає повну інформацію про проект за його slug, включаючи етапи та бонуси",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Унікальний slug проекту",
     *
     *         @OA\Schema(type="string"),
     *         example="miy-noviy-proekt-abc123"
     *     ),
     *
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Деталі проекту",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Project")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Проект не знайдено",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(string $slug): ProjectResource
    {
        $project = Project::query()
            ->with([
                'user.profilePersonal',
                'user.profileLegal',
                'stages' => fn ($q) => $q->orderBy('order'),
                'bonuses' => fn ($q) => $q->orderBy('order'),
            ])
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->where('slug', $slug)
            ->firstOrFail();

        return new ProjectResource($project);
    }

    /**
     * Отримати список меценатів проєкту
     *
     * @OA\Get(
     *     path="/v1/projects/{slug}/donors",
     *     operationId="getProjectDonors",
     *     tags={"Projects"},
     *     summary="Список меценатів проекту",
     *     description="Повертає список користувачів, які зробили донати на проект (відсортовано за сумою)",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug проекту",
     *
     *         @OA\Schema(type="string"),
     *         example="miy-noviy-proekt-abc123"
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Кількість на сторінку (макс. 50)",
     *
     *         @OA\Schema(type="integer", default=20, maximum=50),
     *         example=20
     *     ),
     *
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en). Якщо не вказано — повертає об'єкт з усіма мовами", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список меценатів",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(type="object",
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Іван П.", description="Ім'я або 'Анонім'"),
     *                     @OA\Property(property="amount", type="number", format="float", example=1000.00),
     *                     @OA\Property(property="currency", type="string", example="UAH"),
     *                     @OA\Property(property="is_anonymous", type="boolean", example=false),
     *                     @OA\Property(property="donated_at", type="string", format="date-time", example="2025-01-05T15:30:00.000Z")
     *                 )
     *             ),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Проект не знайдено")
     * )
     */
    public function donors(string $slug, Request $request): \Illuminate\Http\JsonResponse
    {
        $project = Project::query()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->where('slug', $slug)
            ->firstOrFail();

        $perPage = min($request->input('per_page', 15), 50);

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

    // ==========================================
    // Приватні методи для фільтрів
    // ==========================================

    private function getFilterCategories(?string $language = null): array
    {
        $categories = [];
        $publicStatuses = array_map(fn ($s) => $s->value, ProjectStatus::publicStatuses());

        foreach (ArtCategory::cases() as $category) {
            $subcategories = [];

            foreach ($category->getSubcategories() as $slug => $nameUk) {
                $count = Project::whereIn('status', $publicStatuses)
                    ->where('art_category', $category->value)
                    ->where('art_subcategory', $slug)
                    ->count();

                $subcategories[] = [
                    'slug' => $slug,
                    'name' => $this->getFilterTranslation($this->getSubcategoryTranslations($slug, $nameUk), $language),
                    'projects_count' => $count,
                ];
            }

            $categories[] = [
                'slug' => $category->value,
                'name' => $this->getFilterTranslation($this->getCategoryTranslations($category), $language),
                'subcategories' => $subcategories,
            ];
        }

        return $categories;
    }

    private function getFilterStatuses(?string $language = null): array
    {
        $statuses = [];

        foreach (ProjectStatus::publicStatuses() as $status) {
            $count = Project::where('status', $status->value)->count();

            $statuses[] = [
                'slug' => $status->value,
                'name' => $this->getFilterTranslation($this->getStatusTranslations($status), $language),
                'projects_count' => $count,
            ];
        }

        return $statuses;
    }

    private function getFilterBudgetRange(): array
    {
        $publicStatuses = array_map(fn ($s) => $s->value, ProjectStatus::publicStatuses());

        $budgetStats = Project::whereIn('status', $publicStatuses)
            ->where('budget_goal', '>', 0)
            ->selectRaw('MIN(budget_goal) as min, MAX(budget_goal) as max')
            ->first();

        if (! $budgetStats->min || ! $budgetStats->max) {
            return [
                'min' => 0.0,
                'max' => 100000000.0,
                'step' => 10000,
                'default_min' => 0.0,
                'default_max' => 100000000.0,
                'currency' => 'UAH',
            ];
        }

        $min = (float) $budgetStats->min;
        $max = (float) $budgetStats->max;

        $minRounded = floor($min / 1000) * 1000;
        $maxRounded = ceil($max / 1000) * 1000;

        return [
            'min' => $minRounded,
            'max' => $maxRounded,
            'step' => 10000,
            'default_min' => $minRounded,
            'default_max' => $maxRounded,
            'currency' => 'UAH',
        ];
    }

    private function getFilterCurrencies(?string $language = null): array
    {
        $currencies = [
            ['code' => 'UAH', 'symbol' => '₴', 'name' => ['uk' => 'Гривня', 'en' => 'Hryvnia'], 'is_default' => false],
            ['code' => 'USD', 'symbol' => '$', 'name' => ['uk' => 'Долар США', 'en' => 'US Dollar'], 'is_default' => false],
            ['code' => 'EUR', 'symbol' => '€', 'name' => ['uk' => 'Євро', 'en' => 'Euro'], 'is_default' => false],
        ];

        return array_map(fn ($currency) => [
            'code' => $currency['code'],
            'symbol' => $currency['symbol'],
            'name' => $this->getFilterTranslation($currency['name'], $language),
            'is_default' => $currency['is_default'],
        ], $currencies);
    }

    private function getFilterSortOptions(?string $language = null): array
    {
        $options = [
            ['slug' => 'newest', 'name' => ['uk' => 'Найновіші', 'en' => 'Newest']],
            ['slug' => 'popular', 'name' => ['uk' => 'Популярні', 'en' => 'Popular']],
            ['slug' => 'ending_soon', 'name' => ['uk' => 'Скоро завершуються', 'en' => 'Ending Soon']],
            ['slug' => 'most_funded', 'name' => ['uk' => 'Найбільше зібрано', 'en' => 'Most Funded']],
        ];

        return array_map(fn ($option) => [
            'slug' => $option['slug'],
            'name' => $this->getFilterTranslation($option['name'], $language),
        ], $options);
    }

    private function getFilterTotalProjects(): int
    {
        $publicStatuses = array_map(fn ($s) => $s->value, ProjectStatus::publicStatuses());

        return Project::whereIn('status', $publicStatuses)->count();
    }

    private function getCategoryTranslations(ArtCategory $category): array
    {
        return match ($category) {
            ArtCategory::Scenic => ['uk' => 'Сценічне мистецтво', 'en' => 'Performing Arts'],
            ArtCategory::Visual => ['uk' => 'Візуальне мистецтво', 'en' => 'Visual Arts'],
            ArtCategory::FineArt => ['uk' => 'Образотворче мистецтво', 'en' => 'Fine Arts'],
            ArtCategory::Literature => ['uk' => 'Література', 'en' => 'Literature'],
            ArtCategory::Music => ['uk' => 'Музичне мистецтво', 'en' => 'Music'],
            ArtCategory::Other => ['uk' => 'Інше', 'en' => 'Other'],
        };
    }

    private function getSubcategoryTranslations(string $slug, string $nameUk): array
    {
        $translations = [
            'directing' => ['uk' => 'Режисура', 'en' => 'Directing'],
            'acting' => ['uk' => 'Акторське мистецтво', 'en' => 'Acting'],
            'choreography' => ['uk' => 'Хореографічне мистецтво', 'en' => 'Choreography'],
            'original_genre' => ['uk' => 'Оригінальний жанр', 'en' => 'Original Genre'],
            'photography' => ['uk' => 'Художня фотографія', 'en' => 'Art Photography'],
            'video' => ['uk' => 'Відеозйомка та монтаж', 'en' => 'Video Production'],
            'cinema' => ['uk' => 'Повнометражний кінематограф', 'en' => 'Feature Film'],
            'ar' => ['uk' => 'Доповнена реальність', 'en' => 'Augmented Reality'],
            'painting' => ['uk' => 'Живопис', 'en' => 'Painting'],
            'sculpture' => ['uk' => 'Скульптура', 'en' => 'Sculpture'],
            'digital' => ['uk' => 'Діджитал', 'en' => 'Digital'],
            'poetry' => ['uk' => 'Поезія', 'en' => 'Poetry'],
            'prose' => ['uk' => 'Проза', 'en' => 'Prose'],
        ];

        return $translations[$slug] ?? ['uk' => $nameUk, 'en' => ucfirst(str_replace('_', ' ', $slug))];
    }

    private function getStatusTranslations(ProjectStatus $status): array
    {
        return match ($status) {
            ProjectStatus::Announced => ['uk' => 'Оголошені', 'en' => 'Announced'],
            ProjectStatus::InProgress => ['uk' => 'В роботі', 'en' => 'In Progress'],
            ProjectStatus::Paused => ['uk' => 'На паузі', 'en' => 'Paused'],
            ProjectStatus::Completed => ['uk' => 'Завершені', 'en' => 'Completed'],
            ProjectStatus::Sold => ['uk' => 'Продані', 'en' => 'Sold'],
            default => ['uk' => $status->getLabel(), 'en' => ucfirst($status->value)],
        };
    }

    private function getFilterTranslation(array $translations, ?string $language): string|array
    {
        if ($language === null) {
            return $translations;
        }

        return $translations[$language] ?? $translations['uk'] ?? reset($translations);
    }
}
