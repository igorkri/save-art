<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreServiceRequest;
use App\Http\Requests\Api\V1\UpdateServiceRequest;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\ArtCategory;
use App\Models\Service;
use App\Models\User;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * API для управління власними послугами (кабінет митця, art-ua-info)
 *
 * @OA\Tag(
 *     name="My Services",
 *     description="API для управління власними послугами (art-ua-info)"
 * )
 */
class MyServiceController extends Controller
{
    public function __construct(
        private ImageProcessingService $imageProcessor
    ) {}

    /**
     * Список власних послуг
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/my/services",
     *     operationId="artUaInfoGetMyServices",
     *     tags={"My Services"},
     *     summary="Список моїх послуг",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(response=200, description="Список послуг", @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Service")))),
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $services = Service::query()
            ->where('serviceable_type', User::class)
            ->where('serviceable_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return ServiceResource::collection($services);
    }

    /**
     * Отримати власну послугу з повними (двомовними) даними для форми редагування
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/my/services/{service}",
     *     operationId="artUaInfoGetMyService",
     *     tags={"My Services"},
     *     summary="Отримати послугу для редагування",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="service", in="path", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Послуга"),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник послуги")
     * )
     */
    public function show(Request $request, Service $service): JsonResponse
    {
        $this->authorizeOwner($request, $service);
        $service->load('artCategory');

        return response()->json(['data' => [
            'slug' => $service->slug,
            'title' => $service->title,
            'description' => $service->description,
            'image_url' => $service->image ? Storage::url($service->image) : null,
            'art_category_slug' => $service->artCategory?->getRootSlug(),
            'art_subcategory_slug' => $service->artCategory?->getSubSlug(),
            'art_category' => $service->artCategory ? [
                'slug' => $service->artCategory->slug,
                'name' => $service->artCategory->getLabel('uk'),
            ] : null,
            'price' => $service->price !== null ? (float) $service->price : null,
            'price_from' => (bool) $service->price_from,
            'currency' => $service->currency?->value,
            'options' => collect($service->options ?? [])
                ->map(fn (array $option) => $option['name'] ?? null)
                ->filter()
                ->values(),
        ]]);
    }

    /**
     * Створити послугу
     *
     * @OA\Post(
     *     path="/v1/art-ua-info/my/services",
     *     operationId="artUaInfoCreateMyService",
     *     tags={"My Services"},
     *     summary="Створити послугу",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="title", ref="#/components/schemas/LocalizedString"),
     *             @OA\Property(property="description", ref="#/components/schemas/LocalizedString", nullable=true),
     *             @OA\Property(property="image", type="string", nullable=true, description="Файл або Base64 data URL"),
     *             @OA\Property(property="art_category", type="string", description="Slug кореневої галузі мистецтва"),
     *             @OA\Property(property="art_subcategory", type="string", nullable=true, description="Slug підкатегорії"),
     *             @OA\Property(property="price", type="number", nullable=true),
     *             @OA\Property(property="price_from", type="boolean", nullable=true),
     *             @OA\Property(property="currency", type="string", enum={"UAH", "USD", "EUR"}, nullable=true),
     *             @OA\Property(property="options", type="array", maxItems=50, @OA\Items(type="object", @OA\Property(property="name", ref="#/components/schemas/LocalizedString")))
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Послугу створено", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Service"))),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=422, description="Помилка валідації", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function store(StoreServiceRequest $request): ServiceResource
    {
        $data = $request->validated();

        $data['art_category_id'] = ArtCategory::resolveIdFromSlugs(
            $data['art_category'] ?? null,
            $data['art_subcategory'] ?? null
        );
        unset($data['art_category'], $data['art_subcategory']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('services', 'public');
        } elseif (! empty($data['image'])) {
            $data['image'] = $this->imageProcessor->saveBase64Image($data['image'], 'services');
        }

        $data['slug'] = Str::slug($data['title']['uk']).'-'.Str::random(6);
        $data['serviceable_type'] = User::class;
        $data['serviceable_id'] = $request->user()->id;

        $service = Service::create($data);

        return new ServiceResource($service->load('artCategory'));
    }

    /**
     * Оновити послугу
     *
     * @OA\Put(
     *     path="/v1/art-ua-info/my/services/{service}",
     *     operationId="artUaInfoUpdateMyService",
     *     tags={"My Services"},
     *     summary="Оновити послугу",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="service", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Послугу оновлено", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/Service"))),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник послуги"),
     *     @OA\Response(response=422, description="Помилка валідації", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        $this->authorizeOwner($request, $service);

        $data = $request->validated();

        $data['art_category_id'] = ArtCategory::resolveIdFromSlugs(
            $data['art_category'] ?? null,
            $data['art_subcategory'] ?? null
        );
        unset($data['art_category'], $data['art_subcategory']);

        if ($request->hasFile('image')) {
            if ($service->image) {
                Storage::disk('public')->delete($service->image);
            }
            $data['image'] = $request->file('image')->store('services', 'public');
        } elseif (! empty($data['image'])) {
            $data['image'] = $this->imageProcessor->processCover($data['image'], $service->image, 'services');
        }

        $service->fill($data);
        $service->save();

        return new ServiceResource($service->load('artCategory'));
    }

    /**
     * Видалити послугу
     *
     * @OA\Delete(
     *     path="/v1/art-ua-info/my/services/{service}",
     *     operationId="artUaInfoDeleteMyService",
     *     tags={"My Services"},
     *     summary="Видалити послугу",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="service", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Послугу видалено"),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник послуги")
     * )
     */
    public function destroy(Request $request, Service $service): JsonResponse
    {
        $this->authorizeOwner($request, $service);

        if ($service->image) {
            Storage::disk('public')->delete($service->image);
        }
        $service->delete();

        return response()->json(['message' => 'Послугу видалено']);
    }

    private function authorizeOwner(Request $request, Service $service): void
    {
        abort_if(
            ! ($service->serviceable_type === User::class && $service->serviceable_id === $request->user()->id),
            403,
            'Ви не є власником цієї послуги'
        );
    }
}
