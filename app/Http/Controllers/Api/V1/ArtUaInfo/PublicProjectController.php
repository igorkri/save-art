<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Enums\ParameterType;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Http\Resources\Api\V1\ProjectResource;
use App\Models\ArtCategory;
use App\Models\Parameter;
use App\Models\Project;
use App\Support\ArtCategoryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * Публічний перегляд art-ua-info-проєктів (список/деталі/донори). Копія
 * App\Http\Controllers\Api\V1\ProjectController (save-art) — відмінність:
 * forArtUaInfo() замість forSaveArt() у базовому запиті. Створення/редагування
 * проєкту з візарда — окремий App\Http\Controllers\Api\V1\ArtUaInfo\ProjectController.
 */
class PublicProjectController extends Controller
{
    /**
     * Отримати список публічних art-ua-info проєктів з фільтрацією та пагінацією
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/projects",
     *     operationId="artUaInfoGetProjects",
     *     tags={"Projects"},
     *     summary="Список публічних art-ua-info проектів з опціями фільтрації",
     *     description="Повертає список публічних art-ua-info проектів з можливістю фільтрації та пагінації.",
     *
     *     @OA\Parameter(name="language", in="query", @OA\Schema(type="string", enum={"uk", "en"})),
     *     @OA\Parameter(name="art_category", in="query", description="Фільтр по категорії (можна вказати кілька через кому)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="art_subcategory", in="query", description="Фільтр по підкатегорії (можна вказати кілька через кому)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Фільтр по статусу (можна вказати кілька через кому)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="tags", in="query", description="Фільтр по тегах (можна вказати кілька через кому)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="parameter_value_id", in="query", description="Фільтр по значеннях характеристик", @OA\Schema(type="string")),
     *     @OA\Parameter(name="user_id", in="query", description="Фільтр по автору", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", description="Пошук по назві та опису", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort_by", in="query", @OA\Schema(type="string", enum={"name", "date"})),
     *     @OA\Parameter(name="sort_dir", in="query", @OA\Schema(type="string", enum={"asc", "desc"})),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15, maximum=50)),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
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
     *             @OA\Property(property="filters", type="object"),
     *             @OA\Property(property="filters_applied", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $language = $request->query('language');
        $supportedLanguages = ['uk', 'en'];
        if ($language && ! in_array($language, $supportedLanguages)) {
            $language = 'uk';
        }

        $cacheKey = $language ? "art_ua_info_project_filters_static_{$language}" : 'art_ua_info_project_filters_static';
        $staticFilters = Cache::remember($cacheKey, 300, function () use ($language) {
            return [
                'sort_options' => $this->getFilterSortOptions($language),
                'total_projects' => $this->getFilterTotalProjects(),
            ];
        });

        $filters = array_merge($staticFilters, [
            'categories' => $this->getFilterCategories($language, $request),
            'statuses' => $this->getFilterStatuses($language, $request),
            'parameters' => $this->getFilterParameters($language, $request),
        ]);

        $query = $this->buildFilteredQuery($request)
            ->with(['user.profileLegal', 'artCategory.parent', 'projectParameters.parameter', 'projectParameters.parameterValue']);

        $sortBy = $request->input('sort_by', 'date');

        if ($sortBy === 'name') {
            $sortDir = $request->input('sort_dir', 'asc');
            $query->orderByRaw('LOWER(title) '.($sortDir === 'desc' ? 'desc' : 'asc'));
        } else {
            $sortDir = $request->input('sort_dir', 'desc');
            $query->orderBy('announced_at', $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min(max(1, $request->input('per_page', 15)), 50);
        $projects = $query->paginate($perPage);

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
        if ($request->filled('tags')) {
            $filters['tags'] = array_map('trim', explode(',', $request->input('tags')));
        }
        if ($request->filled('parameter_value_id')) {
            $filters['parameter_value_id'] = array_map('trim', explode(',', $request->input('parameter_value_id')));
        }
        if ($request->filled('user_id')) {
            $filters['user_id'] = (int) $request->input('user_id');
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
     * @param  string[]  $except
     */
    private function buildFilteredQuery(Request $request, array $except = []): Builder
    {
        $query = Project::query()->forArtUaInfo()->whereIn('status', ProjectStatus::publicStatuses());

        if (! in_array('art_category', $except, true) && $request->filled('art_category')) {
            $categoryIds = ArtCategoryFilter::resolveCategoryIds($request->input('art_category'));
            if (! empty($categoryIds)) {
                $query->whereIn('art_category_id', $categoryIds);
            }
        }

        if (! in_array('art_subcategory', $except, true) && $request->filled('art_subcategory')) {
            $subIds = ArtCategoryFilter::resolveSubcategoryIds($request->input('art_subcategory'));
            if (! empty($subIds)) {
                $query->whereIn('art_category_id', $subIds);
            }
        }

        if (! in_array('status', $except, true) && $request->filled('status')) {
            $statuses = array_map('trim', explode(',', $request->input('status')));
            $publicStatuses = array_map(fn ($s) => $s->value, ProjectStatus::publicStatuses());
            $validStatuses = array_intersect($statuses, $publicStatuses);
            if (! empty($validStatuses)) {
                $query->whereIn('status', $validStatuses);
            }
        }

        if ($request->filled('tags')) {
            $tags = array_map('trim', explode(',', $request->input('tags')));
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        if (! in_array('parameter_value_id', $except, true) && $request->filled('parameter_value_id')) {
            $valueIds = array_map('intval', array_map('trim', explode(',', $request->input('parameter_value_id'))));
            $query->whereHas('projectParameters', function (Builder $q) use ($valueIds) {
                $q->whereIn('parameter_value_id', $valueIds);
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Отримати деталі art-ua-info проєкту за slug
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/projects/{slug}",
     *     operationId="artUaInfoGetProject",
     *     tags={"Projects"},
     *     summary="Деталі проекту",
     *     description="Повертає повну інформацію про art-ua-info проект за його slug.",
     *
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="language", in="query", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(response=200, description="Деталі проекту", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Project"))),
     *     @OA\Response(response=404, description="Проект не знайдено", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(Request $request, string $slug): ProjectResource
    {
        $project = Project::query()
            ->with([
                'user.profileLegal',
                'projectParameters.parameter',
                'projectParameters.parameterValue',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $isOwner = $request->user('sanctum')?->id === $project->user_id;
        $canModerate = $request->user('sanctum')?->role->canModerate() ?? false;

        if (! $isOwner && ! $canModerate && ! in_array($project->status, ProjectStatus::publicStatuses())) {
            abort(404);
        }

        return new ProjectResource($project);
    }

    /**
     * Отримати список меценатів art-ua-info проєкту
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/projects/{slug}/donors",
     *     operationId="artUaInfoGetProjectDonors",
     *     tags={"Projects"},
     *     summary="Список меценатів проекту",
     *     description="Повертає список користувачів, які зробили донати на проект.",
     *
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20, maximum=50)),
     *     @OA\Parameter(name="language", in="query", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(response=200, description="Список меценатів"),
     *     @OA\Response(response=404, description="Проект не знайдено")
     * )
     */
    public function donors(string $slug, Request $request): JsonResponse
    {
        $project = Project::query()
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->where('slug', $slug)
            ->firstOrFail();

        $perPage = min($request->input('per_page', 15), 50);

        $donations = $project->donations()
            ->with('user.profileSocial')
            ->whereIn('status', ['pending', 'paid'])
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

    private function getFilterCategories(?string $language, Request $request): array
    {
        $categories = [];
        $baseQuery = $this->buildFilteredQuery($request, ['art_subcategory']);

        foreach (ArtCategory::with('children')->whereNull('parent_id')->orderBy('sort_order')->get() as $root) {
            $subcategories = [];

            foreach ($root->children as $child) {
                $count = (clone $baseQuery)->where('art_category_id', $child->id)->count();
                $subcategories[] = [
                    'slug' => $child->slug,
                    'name' => $child->name,
                    'projects_count' => $count,
                ];
            }

            $categories[] = [
                'slug' => $root->slug,
                'name' => $root->name,
                'subcategories' => $subcategories,
            ];
        }

        return $categories;
    }

    private function getFilterStatuses(?string $language, Request $request): array
    {
        $statuses = [];
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

        $baseQuery = $this->buildFilteredQuery($request, ['parameter_value_id']);

        $parameters = Parameter::where('art_category_id', $categoryId)
            ->where('type', ParameterType::List)
            ->orderBy('sort_order')
            ->with('values')
            ->get();

        return $parameters
            ->filter(fn (Parameter $parameter) => $parameter->values->isNotEmpty())
            ->map(function (Parameter $parameter) use ($baseQuery) {
                return [
                    'id' => $parameter->id,
                    'name' => $parameter->name,
                    'values' => $parameter->values->map(function ($value) use ($baseQuery) {
                        $count = (clone $baseQuery)->whereHas('projectParameters', function (Builder $q) use ($value) {
                            $q->where('parameter_value_id', $value->id);
                        })->count();

                        return [
                            'id' => $value->id,
                            'value' => $value->value,
                            'projects_count' => $count,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function getFilterSortOptions(?string $language = null): array
    {
        $options = [
            ['slug' => 'date', 'name' => ['uk' => 'За датою', 'en' => 'By date']],
            ['slug' => 'name', 'name' => ['uk' => 'За назвою', 'en' => 'By name']],
        ];

        return array_map(fn ($option) => [
            'slug' => $option['slug'],
            'name' => $this->getFilterTranslation($option['name'], $language),
        ], $options);
    }

    private function getFilterTotalProjects(): int
    {
        $publicStatuses = array_map(fn ($s) => $s->value, ProjectStatus::publicStatuses());

        return Project::forArtUaInfo()->whereIn('status', $publicStatuses)->count();
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
