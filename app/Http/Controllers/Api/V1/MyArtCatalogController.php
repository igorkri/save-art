<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreArtCatalogRequest;
use App\Http\Requests\Api\V1\UpdateArtCatalogRequest;
use App\Http\Resources\Api\V1\ArtCatalogResource;
use App\Models\ArtCatalog;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use OpenApi\Annotations as OA;

/**
 * API для управління власними каталогами (кабінет митця, art-ua-info)
 *
 * @OA\Tag(
 *     name="My Art Catalogs",
 *     description="API для управління власними PDF-каталогами робіт (art-ua-info)"
 * )
 */
class MyArtCatalogController extends Controller
{
    public function __construct(
        private ImageProcessingService $imageProcessor
    ) {}

    /**
     * Список власних каталогів
     *
     * @OA\Get(
     *     path="/v1/art-ua-info/my/catalogs",
     *     operationId="artUaInfoGetMyCatalogs",
     *     tags={"My Art Catalogs"},
     *     summary="Список моїх каталогів",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Response(response=200, description="Список каталогів", @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ArtCatalog")))),
     *     @OA\Response(response=401, description="Не авторизовано")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $catalogs = ArtCatalog::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return ArtCatalogResource::collection($catalogs);
    }

    /**
     * Створити каталог
     *
     * @OA\Post(
     *     path="/v1/art-ua-info/my/catalogs",
     *     operationId="artUaInfoCreateMyCatalog",
     *     tags={"My Art Catalogs"},
     *     summary="Створити каталог",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *
     *                 @OA\Property(property="title[uk]", type="string", example="Мій каталог"),
     *                 @OA\Property(property="title[en]", type="string", nullable=true),
     *                 @OA\Property(property="image", type="string", format="binary", description="Обкладинка (файл) або Base64 data URL"),
     *                 @OA\Property(property="pdf_file", type="string", format="binary", description="PDF-файл каталогу (до 20MB)"),
     *                 @OA\Property(property="art_category_id", type="integer", nullable=true),
     *                 @OA\Property(property="published_at", type="string", format="date", nullable=true),
     *                 @OA\Property(property="is_primary", type="boolean", nullable=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Каталог створено", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/ArtCatalog"))),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=422, description="Помилка валідації", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function store(StoreArtCatalogRequest $request): ArtCatalogResource
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('catalogs', 'public');
        } else {
            $data['image'] = $this->imageProcessor->saveBase64Image($data['image'], 'catalogs');
        }

        if ($request->hasFile('pdf_file')) {
            $path = $request->file('pdf_file')->store('catalogs', 'public');
            $data['pdf_file'] = basename($path);
        }

        $catalog = new ArtCatalog($data);
        $catalog->user_id = $request->user()->id;

        if (! empty($data['is_primary'])) {
            $this->clearOtherPrimary($request->user()->id);
        }

        $catalog->save();

        return new ArtCatalogResource($catalog->load(['artCategory', 'user']));
    }

    /**
     * Оновити каталог
     *
     * @OA\Put(
     *     path="/v1/art-ua-info/my/catalogs/{catalog}",
     *     operationId="artUaInfoUpdateMyCatalog",
     *     tags={"My Art Catalogs"},
     *     summary="Оновити каталог",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="catalog", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Каталог оновлено", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/ArtCatalog"))),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник каталогу"),
     *     @OA\Response(response=422, description="Помилка валідації", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function update(UpdateArtCatalogRequest $request, ArtCatalog $catalog): ArtCatalogResource
    {
        $this->authorizeOwner($request, $catalog);

        $data = $request->validated();

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($catalog->image);
            $data['image'] = $request->file('image')->store('catalogs', 'public');
        } elseif (! empty($data['image'])) {
            $data['image'] = $this->imageProcessor->processCover($data['image'], $catalog->image);
        }

        if ($request->hasFile('pdf_file')) {
            $path = $request->file('pdf_file')->store('catalogs', 'public');
            $data['pdf_file'] = basename($path);
        }

        if (! empty($data['is_primary'])) {
            $this->clearOtherPrimary($request->user()->id, $catalog->id);
        }

        $catalog->fill($data);
        $catalog->save();

        return new ArtCatalogResource($catalog->load(['artCategory', 'user']));
    }

    /**
     * Видалити каталог
     *
     * @OA\Delete(
     *     path="/v1/art-ua-info/my/catalogs/{catalog}",
     *     operationId="artUaInfoDeleteMyCatalog",
     *     tags={"My Art Catalogs"},
     *     summary="Видалити каталог",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="catalog", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Каталог видалено"),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник каталогу")
     * )
     */
    public function destroy(Request $request, ArtCatalog $catalog): JsonResponse
    {
        $this->authorizeOwner($request, $catalog);

        Storage::disk('public')->delete($catalog->image);
        $catalog->delete();

        return response()->json(['message' => 'Каталог видалено']);
    }

    /**
     * Призначити каталог основним (для показу на головній/сторінці митця)
     *
     * @OA\Post(
     *     path="/v1/art-ua-info/my/catalogs/{catalog}/primary",
     *     operationId="artUaInfoSetPrimaryMyCatalog",
     *     tags={"My Art Catalogs"},
     *     summary="Зробити каталог основним",
     *     security={{"sanctum":{}, "apiKey":{}}},
     *
     *     @OA\Parameter(name="catalog", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Каталог призначено основним", @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/ArtCatalog"))),
     *     @OA\Response(response=401, description="Не авторизовано"),
     *     @OA\Response(response=403, description="Не власник каталогу")
     * )
     */
    public function setPrimary(Request $request, ArtCatalog $catalog): ArtCatalogResource
    {
        $this->authorizeOwner($request, $catalog);

        $this->clearOtherPrimary($request->user()->id, $catalog->id);
        $catalog->is_primary = true;
        $catalog->save();

        return new ArtCatalogResource($catalog->load(['artCategory', 'user']));
    }

    private function authorizeOwner(Request $request, ArtCatalog $catalog): void
    {
        abort_if($catalog->user_id !== $request->user()->id, 403, 'Ви не є власником цього каталогу');
    }

    private function clearOtherPrimary(int $userId, ?int $exceptCatalogId = null): void
    {
        ArtCatalog::query()
            ->where('user_id', $userId)
            ->when($exceptCatalogId, fn ($query) => $query->where('id', '!=', $exceptCatalogId))
            ->update(['is_primary' => false]);
    }
}
