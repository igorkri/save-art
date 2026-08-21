@php
    use App\Enums\ModerationStatus;
    use App\Enums\ProjectSource;
    use App\Enums\ProjectStatus;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    /** @var \App\Models\Project $project */
    $project = $getRecord();
    $isModeration = $project->status === ProjectStatus::Moderation;
    $isRejected = $project->status === ProjectStatus::Rejected;
    $isDraft = in_array($project->status, [ProjectStatus::New, ProjectStatus::Draft], true);
    $isWork = $project->source === ProjectSource::ArtUaInfo
        || $project->source === ProjectSource::ArtUaInfo->value;
    $isStateCard = $isDraft || (! $isWork && ($isModeration || $isRejected));

    $authorName = $project->team?->name ?: $project->user?->display_name ?: __('profile_projects.card.unknown_author');
    $authorAvatar = $project->team?->avatar
        ? (Str::startsWith($project->team->avatar, ['http://', 'https://', 'data:image/'])
            ? $project->team->avatar
            : Storage::disk('public')->url($project->team->avatar))
        : $project->user?->getFilamentAvatarUrl();

    $coverUrl = blank($project->cover)
        ? null
        : (Str::startsWith($project->cover, ['http://', 'https://', 'data:image/'])
            ? $project->cover
            : Storage::disk('public')->url($project->cover));

    $currency = $project->currency?->value ?? 'UAH';
    $collected = number_format((float) $project->budget_collected, 0, ',', ' ');
    $goal = number_format((float) $project->budget_goal, 0, ',', ' ');
    $progress = $project->getProgressPercentage();
    $donors = $project->donations->pluck('user')->filter()->unique('id')->take(5);

    $collectionStatus = match ($project->status) {
        ProjectStatus::Announced, ProjectStatus::InProgress => __('profile_projects.card.collection_active'),
        ProjectStatus::Paused => __('profile_projects.card.collection_paused'),
        ProjectStatus::Completed, ProjectStatus::Sold => __('profile_projects.card.collection_completed'),
        default => $project->status->getLabel(),
    };

    [$stateIcon, $stateText, $stateClass] = match (true) {
        $isRejected => ['heroicon-o-no-symbol', __('profile_projects.card.rejected'), 'is-rejected'],
        $isDraft => ['heroicon-o-pencil-square', __('profile_projects.card.draft'), 'is-draft'],
        $project->status_moderation === ModerationStatus::Processing => [
            'heroicon-o-cog-6-tooth',
            __('profile_projects.card.moderation_processing'),
            'is-processing',
        ],
        default => ['heroicon-o-cog-6-tooth', __('profile_projects.card.moderation_pending'), 'is-pending'],
    };

    $announcedDate = $project->announced_at ?: $project->created_at;
@endphp

<article class="profile-project-card {{ $isStateCard ? 'is-state-card '.$stateClass : '' }}">
    <header class="profile-project-card-author">
        @if ($authorAvatar)
            <img src="{{ $authorAvatar }}" alt="" class="profile-project-card-author-avatar">
        @else
            <span class="profile-project-card-author-avatar profile-project-card-author-fallback" aria-hidden="true">
                {{ Str::upper(Str::substr($authorName, 0, 1)) }}
            </span>
        @endif

        <span class="profile-project-card-author-name">{{ $authorName }}</span>
    </header>

    <div class="profile-project-card-cover-ctn">
        @if ($coverUrl)
            <img src="{{ $coverUrl }}" alt="" class="profile-project-card-cover" loading="lazy">
        @else
            <div class="profile-project-card-cover-fallback" aria-hidden="true">
                <img src="{{ asset('img/masks.webp') }}" alt="">
            </div>
        @endif
    </div>

    <h2 class="profile-project-card-title">{{ $project->title }}</h2>

    @if ($isWork && ! $isDraft)
        <div class="profile-project-card-state profile-project-card-work-info">
            <x-filament::icon icon="heroicon-o-paint-brush" />
            <p>{{ __('profile_projects.card.work') }}</p>
        </div>

        <dl class="profile-project-card-state-date">
            <dt>{{ __('profile_projects.card.created_date') }}</dt>
            <dd>{{ $project->created_at->format('d m Y') }}</dd>
        </dl>
    @elseif ($isStateCard)
        <div class="profile-project-card-state">
            <x-filament::icon :icon="$stateIcon" />
            <p>{{ $stateText }}</p>
        </div>

        <dl class="profile-project-card-state-date">
            <dt>{{ __('profile_projects.card.announcement_date') }}</dt>
            <dd>{{ $announcedDate->format('d m Y') }}</dd>
        </dl>
    @else
        <div class="profile-project-card-funding">
            <div class="profile-project-card-funding-values">
                <strong>{{ $collected }}</strong>

                <span>
                    <small>{{ $currency }}</small>
                    <b>{{ $goal }}</b>
                </span>
            </div>

            <div
                class="profile-project-card-progress"
                role="progressbar"
                aria-valuenow="{{ round($progress) }}"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <span style="width: {{ $progress }}%"></span>
            </div>

            <div class="profile-project-card-donors">
                <span>{{ number_format($project->donors_count, 0, ',', ' ') }}</span>

                @if ($donors->isNotEmpty())
                    <ul aria-label="{{ __('profile_projects.fields.donors_count') }}">
                        @foreach ($donors as $donor)
                            <li>
                                @if ($donor->getFilamentAvatarUrl())
                                    <img src="{{ $donor->getFilamentAvatarUrl() }}" alt="">
                                @else
                                    <span aria-hidden="true">{{ Str::upper(Str::substr($donor->display_name, 0, 1)) }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <dl class="profile-project-card-meta">
            <div>
                <dt>{{ $project->getArtCategoryLabel() ?: __('profile_projects.defaults.empty') }}</dt>
                <dd>{{ $project->getArtSubcategoryLabel() ?: __('profile_projects.defaults.empty') }}</dd>
            </div>
            <div>
                <dt>{{ __('profile_projects.model.singular') }}</dt>
                <dd class="is-status">
                    <x-filament::icon :icon="$project->status->getIcon()" />
                    {{ $project->status->getLabel() }}
                </dd>
            </div>
            <div>
                <dt>{{ __('profile_projects.card.collection') }}</dt>
                <dd>{{ $collectionStatus }}</dd>
            </div>
            <div>
                <dt>{{ __('profile_projects.card.announcement_date') }}</dt>
                <dd>{{ $announcedDate->format('d m Y') }}</dd>
            </div>
            @if ($project->completed_at)
                <div>
                    <dt>{{ __('profile_projects.card.completion_date') }}</dt>
                    <dd>{{ $project->completed_at->format('d m Y') }}</dd>
                </div>
            @endif
        </dl>
    @endif
</article>
