@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    /** @var \App\Models\Team $team */
    $team = $getRecord();
    $members = $team->teamMembers->pluck('user')->filter();
    $visibleMembers = $members->take(14);
    $hasMoreMembers = $members->count() > $visibleMembers->count();
    $avatarUrl = blank($team->avatar)
        ? null
        : (Str::startsWith($team->avatar, ['http://', 'https://', 'data:image/'])
            ? $team->avatar
            : Storage::disk('public')->url($team->avatar));
@endphp

<article class="profile-team-card">
    <header class="profile-team-card-header">
        @if ($avatarUrl)
            <img
                src="{{ $avatarUrl }}"
                alt=""
                class="profile-team-card-avatar"
            >
        @else
            <span class="profile-team-card-avatar profile-team-card-avatar-fallback" aria-hidden="true">
                {{ Str::upper(Str::substr($team->name, 0, 1)) }}
            </span>
        @endif

        <h2 class="profile-team-card-title">{{ $team->name }}</h2>
    </header>

    @if (filled($team->description))
        <p class="profile-team-card-description">{{ $team->description }}</p>
    @endif

    @if (filled($team->specialization))
        <p class="profile-team-card-specialization">{{ $team->specialization }}</p>
    @endif

    @if ($members->isNotEmpty())
        <ul class="profile-team-card-members" aria-label="{{ __('profile_teams.sections.members') }}">
            @foreach ($visibleMembers as $member)
                <li class="profile-team-card-member">
                    @if ($member->getFilamentAvatarUrl())
                        <img
                            src="{{ $member->getFilamentAvatarUrl() }}"
                            alt=""
                            class="profile-team-card-member-avatar"
                        >
                    @else
                        <span class="profile-team-card-member-avatar profile-team-card-avatar-fallback" aria-hidden="true">
                            {{ Str::upper(Str::substr($member->display_name, 0, 1)) }}
                        </span>
                    @endif

                    <span class="profile-team-card-member-name">{{ $member->display_name }}</span>
                </li>
            @endforeach

            @if ($hasMoreMembers)
                <li class="profile-team-card-members-more" aria-label="{{ __('profile_teams.table.members_count') }}: {{ $members->count() }}">
                    …
                </li>
            @endif
        </ul>
    @endif
</article>
