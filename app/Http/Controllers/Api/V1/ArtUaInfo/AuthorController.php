<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Enums\ParameterType;
use App\Enums\ProfileType;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ArtistResource;
use App\Http\Resources\Api\V1\ProjectListResource;
use App\Http\Resources\Api\V1\UserPhotoResource;
use App\Models\ArtCategory;
use App\Models\Parameter;
use App\Models\User;
use App\Support\ArtCategoryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Спільна логіка для публічних профілів "авторів" (митців і організацій) art-ua-info —
 * обидва зберігаються в таблиці users, відрізняються лише profile_type.
 *
 * Копія App\Http\Controllers\Api\V1\AuthorController (save-art), єдина відмінність —
 * forArtUaInfo() замість forSaveArt() у проєктах автора, плюс фільтр/лічильники по
 * галузі мистецтва (art_category/art_subcategory) — потрібно для /authors на фронті,
 * симетрично до PublicProjectController::index на /projects.
 */
abstract class AuthorController extends Controller
{
    /**
     * Тип профілю, за яким фільтрується вибірка. `null` — без фільтра.
     */
    abstract protected function profileType(): ?ProfileType;

    protected function indexQuery(Request $request): AnonymousResourceCollection
    {
        $language = $request->query('language');
        $supportedLanguages = ['uk', 'en'];
        if ($language && ! in_array($language, $supportedLanguages)) {
            $language = 'uk';
        }

        $query = $this->baseAuthorsQuery($request);

        if ($request->filled('art_category') || $request->filled('art_subcategory')) {
            $categoryIds = $request->filled('art_subcategory')
                ? ArtCategoryFilter::resolveSubcategoryIds($request->input('art_subcategory'))
                : ArtCategoryFilter::resolveCategoryIds($request->input('art_category'));

            if (! empty($categoryIds)) {
                $query->whereHas('projects', fn ($q) => $q->forArtUaInfo()->ownedIndividually()
                    ->whereIn('status', ProjectStatus::publicStatuses())
                    ->whereIn('art_category_id', $categoryIds));
            }
        }

        if ($request->filled('parameter_value_id')) {
            $valueIds = array_map('intval', array_map('trim', explode(',', $request->input('parameter_value_id'))));
            $query->whereHas('projects', fn ($q) => $q->forArtUaInfo()->ownedIndividually()
                ->whereIn('status', ProjectStatus::publicStatuses())
                ->whereHas('projectParameters', fn ($pq) => $pq->whereIn('parameter_value_id', $valueIds)));
        }

        $sortBy = $request->input('sort_by', 'projects_count');
        if ($sortBy === 'name') {
            $query->orderByRaw('LOWER(full_name) asc');
        } else {
            $query->orderByDesc('projects_count');
        }

        $perPage = min($request->input('per_page', 20), 50);
        $authors = $query->paginate($perPage);

        return ArtistResource::collection($authors)->additional([
            'filters' => [
                'categories' => $this->getFilterCategories($request, $language),
                'parameters' => $this->getFilterParameters($request, $language),
                'sort_options' => $this->getFilterSortOptions($language),
            ],
        ]);
    }

    /**
     * Базовий запит авторів (з опційним пошуком) — спільний для списку та підрахунку
     * лічильників категорій. Навмисно не враховує art_category/art_subcategory —
     * ними керують окремо (індекс проти саме обраної категорії, лічильники — проти
     * кожної категорії по черзі).
     */
    private function baseAuthorsQuery(Request $request): Builder
    {
        $query = User::query()
            ->when($this->profileType(), fn ($q, $type) => $q->where('profile_type', $type))
            ->withCount([
                'projects' => fn ($q) => $q->forArtUaInfo()->ownedIndividually()->whereIn('status', ProjectStatus::publicStatuses()),
            ])
            ->whereHas('projects', fn ($q) => $q->forArtUaInfo()->ownedIndividually()->whereIn('status', ProjectStatus::publicStatuses()));

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"]);
        }

        return $query;
    }

    /**
     * Категорії/підкатегорії з authors_count — кількість авторів (цього типу профілю),
     * що мають опублікований art-ua-info проєкт у відповідній галузі. За зразком
     * PublicProjectController::getFilterCategories.
     */
    private function getFilterCategories(Request $request, ?string $language): array
    {
        $categories = [];
        $baseQuery = $this->baseAuthorsQuery($request);

        foreach (ArtCategory::with('children')->whereNull('parent_id')->orderBy('sort_order')->get() as $root) {
            $subcategories = [];

            foreach ($root->children as $child) {
                $count = (clone $baseQuery)->whereHas('projects', fn ($q) => $q->forArtUaInfo()->ownedIndividually()
                    ->whereIn('status', ProjectStatus::publicStatuses())
                    ->where('art_category_id', $child->id))->count();

                $subcategories[] = [
                    'slug' => $child->slug,
                    'name' => $child->name,
                    'authors_count' => $count,
                ];
            }

            $rootCategoryIds = [$root->id, ...$root->children->pluck('id')];
            $rootCount = (clone $baseQuery)->whereHas('projects', fn ($q) => $q->forArtUaInfo()->ownedIndividually()
                ->whereIn('status', ProjectStatus::publicStatuses())
                ->whereIn('art_category_id', $rootCategoryIds))->count();

            $categories[] = [
                'slug' => $root->slug,
                'name' => $root->name,
                'authors_count' => $rootCount,
                'subcategories' => $subcategories,
            ];
        }

        return $categories;
    }

    /**
     * Характеристики (Parameter) обраної категорії/підкатегорії з authors_count —
     * кількість авторів, що мають опублікований art-ua-info проєкт із цим значенням
     * характеристики. За зразком PublicProjectController::getFilterParameters.
     * Порожньо, якщо категорію ще не обрано (як і на /projects — параметри прив'язані
     * до конкретної art_category).
     */
    private function getFilterParameters(Request $request, ?string $language): array
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

        $baseQuery = $this->baseAuthorsQuery($request);
        if ($categorySlug || $subcategorySlug) {
            $categoryIds = $subcategorySlug
                ? ArtCategoryFilter::resolveSubcategoryIds($subcategorySlug)
                : ArtCategoryFilter::resolveCategoryIds($categorySlug);

            if (! empty($categoryIds)) {
                $baseQuery->whereHas('projects', fn ($q) => $q->forArtUaInfo()->ownedIndividually()
                    ->whereIn('status', ProjectStatus::publicStatuses())
                    ->whereIn('art_category_id', $categoryIds));
            }
        }

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
                        $count = (clone $baseQuery)->whereHas('projects', fn ($q) => $q->forArtUaInfo()->ownedIndividually()
                            ->whereIn('status', ProjectStatus::publicStatuses())
                            ->whereHas('projectParameters', fn ($pq) => $pq->where('parameter_value_id', $value->id)))
                            ->count();

                        return [
                            'id' => $value->id,
                            'value' => $value->value,
                            'authors_count' => $count,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function getFilterSortOptions(?string $language): array
    {
        $options = [
            ['slug' => 'projects_count', 'name' => ['uk' => 'За популярністю', 'en' => 'By popularity']],
            ['slug' => 'name', 'name' => ['uk' => "За ім'ям", 'en' => 'By name']],
        ];

        return array_map(fn ($option) => [
            'slug' => $option['slug'],
            'name' => $this->getFilterTranslation($option['name'], $language),
        ], $options);
    }

    private function getFilterTranslation(array $translations, ?string $language): string|array
    {
        if ($language === null) {
            return $translations;
        }

        return $translations[$language] ?? $translations['uk'] ?? reset($translations);
    }

    protected function showQuery(string $slug): ArtistResource
    {
        $author = User::query()
            ->when($this->profileType(), fn ($q, $type) => $q->where('profile_type', $type))
            ->with(['profileSocial'])
            ->withCount([
                'projects' => fn ($q) => $q->forArtUaInfo()->ownedIndividually()->whereIn('status', ProjectStatus::publicStatuses()),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ArtistResource($author);
    }

    protected function projectsQuery(string $slug, Request $request): AnonymousResourceCollection
    {
        $author = User::query()
            ->when($this->profileType(), fn ($q, $type) => $q->where('profile_type', $type))
            ->where('slug', $slug)
            ->firstOrFail();

        $query = $author->projects()
            ->forArtUaInfo()
            ->ownedIndividually()
            ->with(['user.profileLegal'])
            ->whereIn('status', ProjectStatus::publicStatuses())
            ->orderBy('announced_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = min($request->input('per_page', 15), 50);
        $projects = $query->paginate($perPage);

        return ProjectListResource::collection($projects);
    }

    protected function photosQuery(string $slug): AnonymousResourceCollection
    {
        $author = User::query()
            ->when($this->profileType(), fn ($q, $type) => $q->where('profile_type', $type))
            ->where('slug', $slug)
            ->firstOrFail();

        return UserPhotoResource::collection($author->photos()->get());
    }
}
