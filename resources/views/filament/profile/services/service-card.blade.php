@php
    use App\Filament\Profile\Resources\Services\ServiceResource;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    /** @var \App\Models\Service $service */
    $service = $getRecord();
    $imageUrl = blank($service->image)
        ? null
        : (Str::startsWith($service->image, ['http://', 'https://', 'data:image/'])
            ? $service->image
            : Storage::disk('public')->url($service->image));

    $currency = $service->currency?->value ?? 'UAH';
    $currencySymbol = match ($currency) {
        'USD' => '$',
        'EUR' => '€',
        default => '₴',
    };
    $price = $service->price === null
        ? __('profile_services.fields.negotiable')
        : (($service->price_from ? __('profile_services.fields.price_from').' ' : '')
            .number_format((float) $service->price, 0, ',', ' ')
            .' '.$currencySymbol);
@endphp

<article class="profile-service-card">
    <div class="profile-service-card-image-ctn">
        @if ($imageUrl)
            <img
                src="{{ $imageUrl }}"
                alt=""
                class="profile-service-card-image"
                loading="lazy"
            >
        @else
            <div class="profile-service-card-image-fallback" aria-hidden="true">
                <x-filament::icon icon="heroicon-o-photo" />
            </div>
        @endif

        <span class="profile-service-card-price">{{ $price }}</span>
    </div>

    <div class="profile-service-card-body">
        <h2 class="profile-service-card-title">{{ $service->title }}</h2>
    </div>

    <a
        href="{{ ServiceResource::getUrl('edit', ['record' => $service]) }}"
        class="profile-service-card-edit"
    >
        <span>{{ __('profile_services.actions.edit') }}</span>
        <x-filament::icon icon="heroicon-o-pencil-square" />
    </a>
</article>
