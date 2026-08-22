@php
    $type = $record->type;
    $data = $record->data ?? [];
    $title = $record->title[app()->getLocale()] ?? $record->title['uk'] ?? '';
    $message = $record->message[app()->getLocale()] ?? $record->message['uk'] ?? '';
    $project = ! empty($data['project_id']) ? \App\Models\Project::find($data['project_id']) : null;
    $projectCoverUrl = blank($project?->cover)
        ? null
        : (\Illuminate\Support\Str::startsWith($project->cover, ['http://', 'https://', 'data:image/'])
            ? $project->cover
            : \Illuminate\Support\Facades\Storage::disk('public')->url($project->cover));
    $amount = $data['amount'] ?? null;
    $goal = $data['goal'] ?? $project?->budget_goal;
    $collected = $data['collected'] ?? $project?->budget_collected;
    $person = $data['donor_name'] ?? null;
    $currency = $data['currency'] ?? 'UAH';
    $legalInfo = $data['legal_info'] ?? null;
    $projectNotification = $project || ! empty($data['project_id']) || ! empty($data['project_slug']);
    $donationNotification = in_array($type, [
        \App\Enums\NotificationType::Donation,
        \App\Enums\NotificationType::DonationReceived,
        \App\Enums\NotificationType::DonationMade,
        \App\Enums\NotificationType::BonusClaimed,
        \App\Enums\NotificationType::ProjectFundingComplete,
    ], true);
    $progress = $goal > 0 && $collected !== null ? min(100, ((float) $collected / (float) $goal) * 100) : null;
@endphp

<article class="profile-notification-card {{ $record->is_read ? 'is-read' : 'is-unread' }} {{ $projectNotification ? 'has-project' : 'is-simple' }} {{ $donationNotification ? 'is-donation' : '' }}">
    <div class="profile-notification-card__icon" aria-hidden="true">
        <x-filament::icon :icon="$type->getIcon()" />
    </div>

    @if ($projectNotification)
        <div class="profile-notification-card__project-visual">
            <div class="profile-notification-card__project-image">
                @if ($projectCoverUrl)
                    <img src="{{ $projectCoverUrl }}" alt="">
                @else
                    <img class="profile-notification-card__project-placeholder" src="{{ asset('img/masks.webp') }}" alt="">
                @endif
            </div>
            <p title="{{ $project?->title ?? ($data['project_title'] ?? __('profile_projects.model.singular')) }}">
                {{ $project?->title ?? ($data['project_title'] ?? __('profile_projects.model.singular')) }}
            </p>
        </div>
    @endif

    <div class="profile-notification-card__body">
        <div class="profile-notification-card__head">
            <h3>{{ $title }}</h3>
            <button
                type="button"
                class="profile-notification-card__close"
                aria-label="Видалити сповіщення"
                wire:click="mountTableAction('delete', '{{ $record->getKey() }}')"
            >×</button>
        </div>

        @if ($person)
            <div class="profile-notification-card__person">
                @if (! empty($data['donor_avatar']))
                    <img src="{{ $data['donor_avatar'] }}" alt="">
                @else
                    <span class="profile-notification-card__person-placeholder">{{ mb_substr($person, 0, 1) }}</span>
                @endif
                <span class="profile-notification-card__person-name">{{ $person }}</span>
            </div>
        @endif

        @if ($message)
            <p class="profile-notification-card__message">{{ $message }}</p>
        @endif

        @if (is_array($legalInfo))
            <dl class="profile-notification-card__details">
                @foreach (['name' => 'Назва', 'address' => 'Адреса', 'email' => 'Електронна пошта', 'phone' => 'Телефон'] as $field => $label)
                    @if (! empty($legalInfo[$field]))
                        <div>
                            <dt>{{ $label }}</dt>
                            <dd>{{ $legalInfo[$field] }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>
        @endif

        @if ($projectNotification && ($amount !== null || $collected !== null || $progress !== null))
            <div class="profile-notification-card__project-info">
                @if ($amount !== null)
                    <div class="profile-notification-card__donation-amount">
                        {{ number_format((float) $amount, 0, '.', ' ') }} <small>{{ $currency }}</small>
                    </div>
                @endif
                @if ($collected !== null || $goal !== null)
                    <div class="profile-notification-card__collected-row">
                        @if ($collected !== null)
                            <div class="profile-notification-card__collected">
                                {{ number_format((float) $collected, 0, '.', ' ') }} <small>{{ $currency }}</small>
                            </div>
                        @endif
                        @if ($goal !== null)
                            <span class="profile-notification-card__goal">{{ number_format((float) $goal, 0, '.', ' ') }}</span>
                        @endif
                    </div>
                @endif
                @if ($progress !== null)
                    <div class="profile-notification-card__progress" role="progressbar" aria-valuenow="{{ round($progress) }}" aria-valuemin="0" aria-valuemax="100">
                        <span style="width: {{ $progress }}%"></span>
                    </div>
                @endif
            </div>
        @endif

        @if ($record->type->value === 'contact_request' && ! empty($data['contact_info_request_id']))
            <div class="profile-notification-card__buttons">
                <button type="button" class="profile-notification-card__button profile-notification-card__button--primary" wire:click="mountTableAction('grantContact', '{{ $record->getKey() }}')">Надати</button>
                <button type="button" class="profile-notification-card__button" wire:click="mountTableAction('rejectContact', '{{ $record->getKey() }}')">Відхилити</button>
            </div>
        @endif
    </div>
</article>
