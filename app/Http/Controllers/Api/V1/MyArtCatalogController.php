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

/**
 * API для управління власними каталогами (кабінет митця)
 */
class MyArtCatalogController extends Controller
{
    public function __construct(
        private ImageProcessingService $imageProcessor
    ) {}

    /**
     * Список власних каталогів
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
