@php
    use Illuminate\Support\Facades\Storage;

    $record = $getRecord();
    $imageUrl = $record->image ? Storage::disk('public')->url($record->image) : null;
    $category = $record->artCategory?->getLabel('uk');
@endphp

<article class="profile-catalog-card">
    <div class="profile-catalog-card-cover-ctn">
        @if ($imageUrl)
            <img class="profile-catalog-card-cover" src="{{ $imageUrl }}" alt="{{ $record->title }}">
        @else
            <div class="profile-catalog-card-cover-fallback">{{ __('profile_catalogs.table.image') }}</div>
        @endif
    </div>

    <h2 class="profile-catalog-card-title">{{ $record->title }}</h2>

    <div class="profile-catalog-card-meta">
        <div>
            <span>{{ __('profile_catalogs.table.category') }}</span>
            <strong>{{ $category ?: '—' }}</strong>
        </div>
        <div>
            <span>{{ __('profile_catalogs.table.likes_count') }}</span>
            <strong>{{ number_format($record->likes_count, 0, '.', ' ') }}</strong>
        </div>
        <div>
            <span>{{ __('profile_catalogs.table.published_at') }}</span>
            <strong>{{ $record->published_at?->format('d.m.Y') ?: '—' }}</strong>
        </div>
    </div>

    @if ($record->is_primary)
        <div class="profile-catalog-card-primary">{{ __('profile_catalogs.table.is_primary') }}</div>
    @endif
</article>
