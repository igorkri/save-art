<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\ArtCategory;
use App\Models\Service;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Services",
 *     description="Послуги митців, організацій та команд"
 * )
 */
class ServiceController extends Controller
{
    /**
     * Отримати список послуг
     *
     * @OA\Get(
     *     path="/v1/services",
     *     operationId="getServices",
     *     tags={"Services"},
     *     summary="Список послуг",
     *     description="Повертає список послуг з можливістю фільтрації за категорією мистецтва, ціною, валютою, локацією та пошуком",
     *
     *     @OA\Parameter(name="art_category", in="query", description="Slug кореневої категорії мистецтва (можна кілька через кому)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="art_subcategory", in="query", description="Slug підкатегорії мистецтва (можна кілька через кому)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="performer_type", in="query", description="Тип виконавця", @OA\Schema(type="string", enum={"artist", "team"})),
     *     @OA\Parameter(name="currency", in="query", description="Валюта", @OA\Schema(type="string", enum={"UAH", "USD", "EUR"})),
     *     @OA\Parameter(name="price_min", in="query", description="Мінімальна ціна", @OA\Schema(type="number")),
     *     @OA\Parameter(name="price_max", in="query", description="Максимальна ціна", @OA\Schema(type="number")),
     *     @OA\Parameter(name="location", in="query", description="Пошук за місцезнаходженням", @OA\Schema(type="string")),
     *     @OA\Parameter(name="search", in="query", description="Пошук по назві та опису послуги", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Кількість на сторінку (макс. 50)", @OA\Schema(type="integer", default=15, maximum=50)),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Список послуг",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Service")),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks"),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->buildFilteredQuery($request)->with(['serviceable', 'artCategory']);
        $query->orderBy('created_at', $request->input('sort_dir') === 'asc' ? 'asc' : 'desc');

        $perPage = min($request->input('per_page', 15), 50);
        $services = $query->paginate($perPage);

        $language = $request->query('language');

        return ServiceResource::collection($services)->additional([
            'filters' => [
                'categories' => $this->getFilterCategories($language, $request),
            ],
        ]);
    }

    /**
     * @param  string[]  $except
     */
    private function buildFilteredQuery(Request $request, array $except = []): Builder
    {
        $query = Service::query();

        if (! in_array('art_subcategory', $except, true) && $request->filled('art_subcategory')) {
            $subSlugs = array_map('trim', explode(',', (string) $request->input('art_subcategory')));
            // Слаг може бути як підкатегорією, так і кореневою категорією без дітей —
            // в обох випадках фільтруємо строго по її власному art_category_id.
            $subIds = ArtCategory::whereIn('slug', $subSlugs)->pluck('id')->all();
            $query->when(! empty($subIds), fn ($q) => $q->whereIn('art_category_id', $subIds));
        } elseif (! in_array('art_category', $except, true) && $request->filled('art_category')) {
            $slugs = array_map('trim', explode(',', (string) $request->input('art_category')));
            $categoryIds = ArtCategory::whereIn('slug', $slugs)
                ->get()
                ->flatMap(fn (ArtCategory $c) => $c->parent_id
                    ? [$c->id]
                    : [$c->id, ...$c->children()->pluck('id')]
                )
                ->unique()
                ->values()
                ->all();
            $query->when(! empty($categoryIds), fn ($q) => $q->whereIn('art_category_id', $categoryIds));
        }

        if ($request->input('performer_type') === 'team') {
            $query->where('serviceable_type', Team::class);
        } elseif ($request->input('performer_type') === 'artist') {
            $query->where('serviceable_type', User::class);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->input('currency'));
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', (float) $request->input('price_min'));
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', (float) $request->input('price_max'));
        }

        if ($request->filled('location')) {
            $location = mb_strtolower((string) $request->input('location'));
            $jsonUnquote = (new Service)->getConnection()->getDriverName() === 'sqlite' ? '%s' : 'JSON_UNQUOTE(%s)';
            $cityUk = sprintf($jsonUnquote, "JSON_EXTRACT(city, '$.uk')");
            $cityEn = sprintf($jsonUnquote, "JSON_EXTRACT(city, '$.en')");
            // Місто послуги власного поля не має — шукаємо за містом виконавця (митця/команди).
            $query->whereHasMorph('serviceable', [User::class, Team::class], function ($q) use ($location, $cityUk, $cityEn) {
                $q->whereRaw("LOWER({$cityUk}) LIKE ?", ["%{$location}%"])
                    ->orWhereRaw("LOWER({$cityEn}) LIKE ?", ["%{$location}%"]);
            });
        }

        if ($request->filled('search')) {
            $search = mb_strtolower((string) $request->input('search'));
            $jsonUnquote = (new Service)->getConnection()->getDriverName() === 'sqlite' ? '%s' : 'JSON_UNQUOTE(%s)';
            $titleUk = sprintf($jsonUnquote, "JSON_EXTRACT(title, '$.uk')");
            $titleEn = sprintf($jsonUnquote, "JSON_EXTRACT(title, '$.en')");
            $descriptionUk = sprintf($jsonUnquote, "JSON_EXTRACT(description, '$.uk')");
            $descriptionEn = sprintf($jsonUnquote, "JSON_EXTRACT(description, '$.en')");
            $query->where(function ($q) use ($search, $titleUk, $titleEn, $descriptionUk, $descriptionEn) {
                $q->whereRaw("LOWER({$titleUk}) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("LOWER({$titleEn}) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("LOWER({$descriptionUk}) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("LOWER({$descriptionEn}) LIKE ?", ["%{$search}%"]);
            });
        }

        return $query;
    }

    /**
     * Дерево категорій (корінь + підкатегорії) з кількістю послуг для кожної —
     * враховує всі фільтри, окрім вибору категорії (щоб чекбокси не зникали одна за одною).
     */
    private function getFilterCategories(?string $language, Request $request): array
    {
        $categories = [];
        $baseQuery = $this->buildFilteredQuery($request, ['art_subcategory', 'art_category']);

        foreach (ArtCategory::with('children')->whereNull('parent_id')->orderBy('sort_order')->get() as $root) {
            $subcategories = [];
            $categoryIds = [$root->id];

            foreach ($root->children as $child) {
                $categoryIds[] = $child->id;
                $count = (clone $baseQuery)->where('art_category_id', $child->id)->count();
                $subcategories[] = [
                    'slug' => $child->slug,
                    'name' => $this->getFilterTranslation(['uk' => $child->getLabel('uk'), 'en' => $child->getLabel('en')], $language),
                    'services_count' => $count,
                ];
            }

            $rootCount = (clone $baseQuery)->whereIn('art_category_id', $categoryIds)->count();

            $categories[] = [
                'slug' => $root->slug,
                'name' => $this->getFilterTranslation(['uk' => $root->getLabel('uk'), 'en' => $root->getLabel('en')], $language),
                'services_count' => $rootCount,
                'subcategories' => $subcategories,
            ];
        }

        return $categories;
    }

    private function getFilterTranslation(array $translations, ?string $language): string|array
    {
        if ($language === null) {
            return $translations;
        }

        return $translations[$language] ?? $translations['uk'] ?? reset($translations);
    }

    /**
     * Підказки міст для фільтра "Місцезнаходження"
     *
     * @OA\Get(
     *     path="/v1/services/locations",
     *     operationId="getServiceLocations",
     *     tags={"Services"},
     *     summary="Автопідказки міст виконавців послуг",
     *     description="Повертає до 8 міст (серед тих, де є хоча б одна послуга), що починаються з пошукового рядка",
     *
     *     @OA\Parameter(name="search", in="query", required=true, description="Початок назви міста", @OA\Schema(type="string")),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(response=200, description="Список міст", @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(type="string"))))
     * )
     */
    public function locations(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        if ($search === '') {
            return response()->json(['data' => []]);
        }

        $language = $request->query('language', 'uk');

        $userIds = Service::query()->where('serviceable_type', User::class)->distinct()->pluck('serviceable_id');
        $teamIds = Service::query()->where('serviceable_type', Team::class)->distinct()->pluck('serviceable_id');

        $cities = User::query()->whereIn('id', $userIds)->pluck('city')
            ->merge(Team::query()->whereIn('id', $teamIds)->pluck('city'));

        $names = $cities
            ->filter()
            ->map(fn (array $city) => $city[$language] ?? $city['uk'] ?? reset($city))
            ->filter()
            ->unique();

        $matches = $names
            ->filter(fn (string $name) => mb_stripos($name, $search) === 0)
            ->sort(fn (string $a, string $b) => mb_strtolower($a) <=> mb_strtolower($b))
            ->values()
            ->take(8);

        return response()->json(['data' => $matches]);
    }

    /**
     * Отримати послугу
     *
     * @OA\Get(
     *     path="/v1/services/{slug}",
     *     operationId="getService",
     *     tags={"Services"},
     *     summary="Послуга за slug",
     *     description="Повертає одну послугу з даними виконавця",
     *
     *     @OA\Parameter(name="slug", in="path", required=true, description="Slug послуги", @OA\Schema(type="string")),
     *     @OA\Parameter(name="language", in="query", description="Мова відповіді (uk, en)", @OA\Schema(type="string", enum={"uk", "en"})),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Послуга",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", ref="#/components/schemas/Service")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Послугу не знайдено")
     * )
     */
    public function show(string $slug): ServiceResource
    {
        $service = Service::query()
            ->with(['serviceable', 'artCategory'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ServiceResource($service);
    }
}
