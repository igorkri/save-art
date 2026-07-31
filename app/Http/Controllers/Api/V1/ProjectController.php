<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ParameterType;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\ArtCategory;
use App\Models\Parameter;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
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
     *     @OA\Parameter(name="parameter_value_id", in="query", description="Фільтр по значеннях характеристик (id зі списку /v1/parameters, можна вказати кілька через кому)", @OA\Schema(type="string"), example="12,15"),
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

        // Статичні опції фільтрації (не залежать від застосованих фільтрів) — кешуємо
        $cacheKey = $language ? "project_filters_static_{$language}" : 'project_filters_static';
        $staticFilters = Cache::remember($cacheKey, 300, function () use ($language) {
            return [
                'budget_range' => $this->getFilterBudgetRange(),
                'currencies' => $this->getFilterCurrencies($language),
                'sort_options' => $this->getFilterSortOptions($language),
                'total_projects' => $this->getFilterTotalProjects(),
            ];
        });

        // Категорії та статуси рахуємо кожного разу facet-методом: кількість проєктів
        // для кожного варіанту враховує решту вже застосованих фільтрів (окрім фільтра
        // цього ж виміру), щоб чекбокси з 0 результатів можна було позначити неактивними.
        $filters = array_merge($staticFilters, [
            'categories' => $this->getFilterCategories($language, $request),
            'statuses' => $this->getFilterStatuses($language, $request),
            'parameters' => $this->getFilterParameters($language, $request),
        ]);

        // Будуємо запит для проєктів (artCategory.parent для коректних лейблів батько/нащадок)
        $query = $this->buildFilteredQuery($request)
            ->with(['user.profileLegal', 'team', 'artCategory.parent', 'projectParameters.parameter', 'projectParameters.parameterValue']);

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

        if ($request->filled('parameter_value_id')) {
            $filters['parameter_value_id'] = array_map('trim', explode(',', $request->input('parameter_value_id')));
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
     * Будує запит проєктів із застосованими фільтрами запиту.
     * $except дозволяє пропустити фільтр(и) певного виміру (наприклад ['status']),
     * щоб порахувати facet-кількість для варіантів цього виміру з урахуванням решти фільтрів.
     *
     * @param  string[]  $except
     */
    private function buildFilteredQuery(Request $request, array $except = []): Builder
    {
        $query = Project::query()->forSaveArt()->whereIn('status', ProjectStatus::publicStatuses());

        // Фільтр по категорії (slug кореня або підкатегорії; множинні значення через кому)
        if (! in_array('art_category', $except, true) && $request->filled('art_category')) {
            $slugs = array_map('trim', explode(',', $request->input('art_category')));
            $categoryIds = ArtCategory::whereIn('slug', $slugs)
                ->get()
                ->flatMap(fn (ArtCategory $c) => $c->parent_id
                    ? [$c->id]
                    : [$c->id, ...$c->children()->pluck('id')]
                )
                ->unique()
                ->values()
                ->all();
            if (! empty($categoryIds)) {
                $query->whereIn('art_category_id', $categoryIds);
            }
        }

        // Фільтр по підкатегорії (slug підкатегорії; множинні значення через кому)
        if (! in_array('art_subcategory', $except, true) && $request->filled('art_subcategory')) {
            $subSlugs = array_map('trim', explode(',', $request->input('art_subcategory')));
            $subIds = ArtCategory::whereNotNull('parent_id')->whereIn('slug', $subSlugs)->pluck('id')->all();
            if (! empty($subIds)) {
                $query->whereIn('art_category_id', $subIds);
            }
        }

        // Фільтр по статусу (підтримує множинні значення через кому)
        if (! in_array('status', $except, true) && $request->filled('status')) {
            $statuses = array_map('trim', explode(',', $request->input('status')));
            $publicStatuses = array_map(fn ($s) => $s->value, ProjectStatus::publicStatuses());
            $validStatuses = array_intersect($statuses, $publicStatuses);
            if (! empty($validStatuses)) {
                $query->whereIn('status', $validStatuses);
            }
        }

        // Фільтр по валюті (підтримує множинні значення через кому)
        if (! in_array('currency', $except, true) && $request->filled('currency')) {
            $currencies = array_map('trim', explode(',', $request->input('currency')));
            $query->whereIn('currency', $currencies);
        }

        // Фільтр по цільовому бюджету
        if (! in_array('budget', $except, true)) {
            if ($request->filled('budget_min')) {
                $query->where('budget_goal', '>=', $request->input('budget_min'));
            }
            if ($request->filled('budget_max')) {
                $query->where('budget_goal', '<=', $request->input('budget_max'));
            }
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

        // Фільтр по значеннях характеристик (множинні значення через кому)
        if (! in_array('parameter_value_id', $except, true) && $request->filled('parameter_value_id')) {
            $valueIds = array_map('intval', array_map('trim', explode(',', $request->input('parameter_value_id'))));
            $query->whereHas('projectParameters', function (Builder $q) use ($valueIds) {
                $q->whereIn('parameter_value_id', $valueIds);
            });
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

        // Пошук по назві та опису (LOWER — щоб не залежати від регістру, бо JSON_EXTRACT повертає бінарне значення)
        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, '$.uk'))) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, '$.en'))) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(short_description, '$.uk'))) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(short_description, '$.en'))) LIKE ?", ["%{$search}%"]);
            });
        }

        return $query;
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
    public function show(Request $request, string $slug): ProjectResource
    {
        $project = Project::query()
            ->with([
                'user.profileLegal',
                'stages' => fn ($q) => $q->orderBy('order'),
                'bonuses' => fn ($q) => $q->orderBy('order'),
                'projectParameters.parameter',
                'projectParameters.parameterValue',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $isOwner = $request->user('sanctum')?->id === $project->user_id;

        if (! $isOwner && ! in_array($project->status, ProjectStatus::publicStatuses())) {
            abort(404);
        }

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
     *     description="Повертає список користувачів, які зробили донати на проект (відсортовано за сумою). Меценати, які приховали донати зі свого публічного профілю, у списку не показуються",
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
     *                     @OA\Property(property="user_slug", type="string", nullable=true, description="Slug для посилання на публічний профіль мецената (null для анонімних)"),
     *                     @OA\Property(property="avatar_url", type="string", nullable=true, example="https://example.com/storage/avatars/1.jpg"),
     *                     @OA\Property(property="social", type="object", nullable=true,
     *                         @OA\Property(property="facebook", type="string", nullable=true),
     *                         @OA\Property(property="twitter", type="string", nullable=true),
     *                         @OA\Property(property="linkedin", type="string", nullable=true),
     *                         @OA\Property(property="pinterest", type="string", nullable=true)
     *                     ),
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
            ->with('user.profileSocial')
            ->whereIn('status', ['pending', 'paid'])
            // Меценат міг приховати свої донати зі свого публічного профілю
            // (is_public=false) — тоді він не повинен світитися і в списку донорів проєкту
            ->where('is_public', true)
            ->orderBy('amount', 'desc')
            ->paginate($perPage);

        $donors = $donations->getCollection()->map(function ($donation) {
            $user = $donation->is_anonymous ? null : $donation->user;

            return [
                'id' => $donation->id,
                'name' => $donation->getDisplayName(),
                'amount' => (float) $donation->amount,
                'currency' => $donation->currency->value,
                'is_anonymous' => $donation->is_anonymous,
                'user_slug' => $user?->slug,
                'avatar_url' => $user?->avatar ? Storage::url($user->avatar) : null,
                'social' => $user?->profileSocial ? [
                    'facebook' => $user->profileSocial->facebook,
                    'twitter' => $user->profileSocial->twitter,
                    'linkedin' => $user->profileSocial->linkedin,
                    'pinterest' => $user->profileSocial->pinterest,
                ] : null,
                'donated_at' => ($donation->paid_at ?? $donation->created_at)?->toISOString(),
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

    private function getFilterCategories(?string $language, Request $request): array
    {
        $categories = [];

        // Виключаємо фільтр підкатегорії з базового запиту: кожен варіант рахуємо
        // окремо з урахуванням решти застосованих фільтрів (статус, бюджет, валюта тощо).
        $baseQuery = $this->buildFilteredQuery($request, ['art_subcategory']);

        foreach (ArtCategory::with('children')->whereNull('parent_id')->orderBy('sort_order')->get() as $root) {
            $subcategories = [];

            foreach ($root->children as $child) {
                $count = (clone $baseQuery)->where('art_category_id', $child->id)->count();
                $subcategories[] = [
                    'slug' => $child->slug,
                    'name' => $this->getFilterTranslation(['uk' => $child->getLabel('uk'), 'en' => $child->getLabel('en')], $language),
                    'projects_count' => $count,
                ];
            }

            $categories[] = [
                'slug' => $root->slug,
                'name' => $this->getFilterTranslation(['uk' => $root->getLabel('uk'), 'en' => $root->getLabel('en')], $language),
                'subcategories' => $subcategories,
            ];
        }

        return $categories;
    }

    private function getFilterStatuses(?string $language, Request $request): array
    {
        $statuses = [];

        // Виключаємо фільтр статусу з базового запиту: кожен варіант рахуємо окремо
        // з урахуванням решти застосованих фільтрів (категорія, бюджет, валюта тощо).
        $baseQuery = $this->buildFilteredQuery($request, ['status']);

        foreach (ProjectStatus::publicStatuses() as $status) {
            $count = (clone $baseQuery)->where('status', $status->value)->count();

            $statuses[] = [
                'slug' => $status->value,
                'name' => $this->getFilterTranslation($this->getStatusTranslations($status), $language),
                'projects_count' => $count,
            ];
        }

        return $statuses;
    }

    /**
     * Характеристики (Parameter/ParameterValue) поточної обраної категорії — доступні лише
     * коли обрана рівно одна категорія/підкатегорія (характеристики належать конкретній
     * категорії, тож без вибору категорії показувати їх нема сенсу).
     */
    private function getFilterParameters(?string $language, Request $request): array
    {
        $categorySlug = $request->filled('art_category')
            ? trim(explode(',', $request->input('art_category'))[0])
            : null;
        $subcategorySlug = $request->filled('art_subcategory')
            ? trim(explode(',', $request->input('art_subcategory'))[0])
            : null;

        $categoryId = ArtCategory::resolveIdFromSlugs($categorySlug, $subcategorySlug);
        if (! $categoryId) {
            return [];
        }

        // Виключаємо фільтр характеристик з базового запиту: кожне значення рахуємо
        // окремо з урахуванням решти застосованих фільтрів (категорія, статус, бюджет тощо).
        $baseQuery = $this->buildFilteredQuery($request, ['parameter_value_id']);

        $parameters = Parameter::where('art_category_id', $categoryId)
            ->where('type', ParameterType::List)
            ->orderBy('sort_order')
            ->with('values')
            ->get();

        return $parameters
            ->filter(fn (Parameter $parameter) => $parameter->values->isNotEmpty())
            ->map(function (Parameter $parameter) use ($baseQuery, $language) {
                return [
                    'id' => $parameter->id,
                    'name' => $this->getFilterTranslation($parameter->getTranslations('name'), $language),
                    'values' => $parameter->values->map(function ($value) use ($baseQuery, $language) {
                        $count = (clone $baseQuery)->whereHas('projectParameters', function (Builder $q) use ($value) {
                            $q->where('parameter_value_id', $value->id);
                        })->count();

                        return [
                            'id' => $value->id,
                            'value' => $this->getFilterTranslation($value->getTranslations('value'), $language),
                            'projects_count' => $count,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function getFilterBudgetRange(): array
    {
        $publicStatuses = array_map(fn ($s) => $s->value, ProjectStatus::publicStatuses());

        $budgetStats = Project::forSaveArt()
            ->whereIn('status', $publicStatuses)
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

        return Project::forSaveArt()->whereIn('status', $publicStatuses)->count();
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
