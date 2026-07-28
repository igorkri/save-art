<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreServiceRequest;
use App\Http\Requests\Api\V1\UpdateServiceRequest;
use App\Http\Resources\Api\V1\ServiceResource;
use App\Models\Service;
use App\Models\User;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * API для управління власними послугами (кабінет митця)
 */
class MyServiceController extends Controller
{
    public function __construct(
        private ImageProcessingService $imageProcessor
    ) {}

    /**
     * Список власних послуг
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
     * Створити послугу
     */
    public function store(StoreServiceRequest $request): ServiceResource
    {
        $data = $request->validated();

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
     */
    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        $this->authorizeOwner($request, $service);

        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($service->image) {
                Storage::disk('public')->delete($service->image);
            }
            $data['image'] = $request->file('image')->store('services', 'public');
        } elseif (! empty($data['image'])) {
            $data['image'] = $this->imageProcessor->processCover($data['image'], $service->image);
        }

        $service->fill($data);
        $service->save();

        return new ServiceResource($service->load('artCategory'));
    }

    /**
     * Видалити послугу
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
