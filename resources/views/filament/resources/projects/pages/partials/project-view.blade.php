@php
    $tagList = function (array|string|null $tags) {
        $tags = is_array($tags) ? $tags : explode(',', (string) $tags);

        return collect($tags)->map(fn ($tag) => trim((string) $tag))->filter();
    };

    $isImageFile = fn (?string $path) => in_array(strtolower(pathinfo($path ?? '', PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'], true);

    $statusColors = [
        'new' => 'gray',
        'draft' => 'gray',
        'moderation' => 'warning',
        'announced' => 'info',
        'in_progress' => 'accent',
        'paused' => 'gray',
        'completed' => 'success',
        'sold' => 'success',
        'rejected' => 'danger',
    ];

    $moderationColors = [
        'pending' => 'warning',
        'processing' => 'info',
        'approved' => 'success',
        'rejected' => 'danger',
    ];

    $dotClasses = [
        'gray' => 'bg-gray-400',
        'warning' => 'bg-amber-400',
        'info' => 'bg-sky-400',
        'accent' => 'bg-[#FECC39]',
        'success' => 'bg-emerald-400',
        'danger' => 'bg-rose-400',
    ];

    $textClasses = [
        'gray' => 'text-gray-300',
        'warning' => 'text-amber-400',
        'info' => 'text-sky-400',
        'accent' => 'text-[#FECC39]',
        'success' => 'text-emerald-400',
        'danger' => 'text-rose-400',
    ];

    $badgeClasses = [
        'gray' => 'bg-white/5 text-gray-300 ring-white/10',
        'warning' => 'bg-amber-400/10 text-amber-400 ring-amber-400/20',
        'info' => 'bg-sky-400/10 text-sky-400 ring-sky-400/20',
        'accent' => 'bg-[#FECC39]/10 text-[#FECC39] ring-[#FECC39]/20',
        'success' => 'bg-emerald-400/10 text-emerald-400 ring-emerald-400/20',
        'danger' => 'bg-rose-400/10 text-rose-400 ring-rose-400/20',
    ];

    $finalResultFiles = ! empty($project->final_result)
        ? ($project->final_result['files'] ?? array_filter([$project->final_result['file'] ?? null]))
        : [];

    $stageStatusColors = [
        'planned' => 'gray',
        'in_progress' => 'accent',
        'completed' => 'success',
    ];

    $tabs = [
        'general' => ['label' => 'Загальне', 'icon' => 'heroicon-o-squares-2x2'],
        'description' => ['label' => 'Опис і теги', 'icon' => 'heroicon-o-document-text'],
        'details' => ['label' => 'Деталі та бюджет', 'icon' => 'heroicon-o-clipboard-document-list'],
        'characteristics' => ['label' => 'Характеристики', 'icon' => 'heroicon-o-adjustments-horizontal'],
        'content' => ['label' => 'Контент', 'icon' => 'heroicon-o-newspaper'],
        'stages' => ['label' => 'Етапи', 'icon' => 'heroicon-o-list-bullet'],
        'bonuses' => ['label' => 'Бонуси', 'icon' => 'heroicon-o-gift'],
        'donations' => ['label' => 'Донори', 'icon' => 'heroicon-o-heart'],
        'result' => ['label' => 'Результат', 'icon' => 'heroicon-o-sparkles'],
    ];

    $title = $project->title ?: $project->slug;
@endphp

@once
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Unbounded:wght@500;600;700&family=Wix+Madefor+Display:wght@400;500;600&display=swap');

        .project-view {
            font-family: 'Wix Madefor Display', ui-sans-serif, system-ui, sans-serif;
        }

        .project-view-display {
            font-family: 'Unbounded', ui-sans-serif, system-ui, sans-serif;
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        .project-view-progress {
            background-image: linear-gradient(90deg, #f7b500 0%, #FECC39 60%, #ffe08a 100%);
            position: relative;
            overflow: hidden;
        }

        .project-view-progress::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: linear-gradient(120deg, transparent 30%, rgba(255, 255, 255, 0.45) 50%, transparent 70%);
            background-size: 200% 100%;
            animation: project-view-shine 2.8s ease-in-out infinite;
        }

        @keyframes project-view-shine {
            0% { background-position: 160% 0; }
            100% { background-position: -60% 0; }
        }

        .project-view [x-cloak] { display: none !important; }

        /* Filament's fi-modal-content is flex-1 without min-height:0/overflow,
           so a fixed-height (Width::Screen) modal can't shrink to let our
           internal panes scroll — force it to constrain here. */
        .fi-modal-content:has(> .project-view) {
            min-height: 0;
            overflow: hidden;
        }
    </style>
@endonce

<div x-data="{ tab: 'general', lightbox: null }" class="project-view -m-6 flex h-full min-h-0 flex-col overflow-hidden bg-[#272727] text-gray-200 lg:flex-row">
    {{-- Sidebar --}}
    <div class="relative flex w-full shrink-0 flex-col gap-6 overflow-y-auto border-b border-white/5 bg-[#1f1f1f] p-6 max-lg:max-h-64 lg:w-80 lg:border-b-0 lg:border-r">
        @if ($project->cover)
            <button
                type="button"
                x-on:click="lightbox = { src: '{{ \Illuminate\Support\Facades\Storage::disk('public')->url($project->cover) }}', alt: '' }"
                class="group relative block w-full cursor-zoom-in overflow-hidden rounded-2xl shadow-lg shadow-black/40 ring-1 ring-white/10"
            >
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($project->cover) }}"
                    alt=""
                    class="h-40 w-full object-cover transition duration-700 ease-out group-hover:scale-105"
                />
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                <p class="absolute bottom-2.5 left-3 right-3 truncate text-sm font-medium tracking-wide text-white/80">{{ $project->code }}</p>
                <span class="pointer-events-none absolute right-2.5 top-2.5 flex h-7 w-7 items-center justify-center rounded-lg bg-black/40 opacity-0 backdrop-blur-sm transition group-hover:opacity-100">
                    <x-filament::icon icon="heroicon-o-magnifying-glass-plus" class="h-3.5 w-3.5 text-white" />
                </span>
            </button>
        @else
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">{{ $project->code }}</p>
            </div>
        @endif

        <div class="space-y-3.5 rounded-xl border border-white/5 bg-white/[0.03] p-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Статус</p>
                <div class="mt-1.5 flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full {{ $dotClasses[$statusColors[$project->status->value]] }} opacity-40"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full {{ $dotClasses[$statusColors[$project->status->value]] }}"></span>
                    </span>
                    <span class="text-base font-semibold {{ $textClasses[$statusColors[$project->status->value]] }}">{{ $project->status->getLabel() }}</span>
                </div>
                @php $nextStatuses = collect($statusOptions)->except($project->status->value); @endphp
                @if ($nextStatuses->isNotEmpty())
                    <div class="mt-3 space-y-1.5">
                        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Перевести в</p>
                        @foreach ($nextStatuses as $value => $label)
                            <button
                                type="button"
                                x-on:click="$wire.moveProject({{ $project->id }}, '{{ $value }}')"
                                class="flex w-full items-center justify-between gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-left text-sm font-medium text-gray-200 transition hover:border-[#FECC39]/50 hover:bg-[#FECC39]/10 hover:text-[#FECC39]"
                            >
                                {{ $label }}
                                <x-filament::icon icon="heroicon-o-arrow-right" class="h-3.5 w-3.5 shrink-0 opacity-60" />
                            </button>
                        @endforeach
                    </div>
                @else
                    <p class="mt-3 text-sm text-gray-300">Кінцевий статус — подальших переходів немає</p>
                @endif
            </div>

            <div class="h-px bg-white/5"></div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Модерація</p>
                <span class="mt-1.5 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-sm font-semibold ring-1 ring-inset {{ $badgeClasses[$moderationColors[$project->status_moderation->value]] }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $dotClasses[$moderationColors[$project->status_moderation->value]] }}"></span>
                    {{ $project->status_moderation->getLabel() }}
                </span>

                @if ($project->status->value === 'moderation' && $project->status_moderation->value === 'pending')
                    <button
                        type="button"
                        x-on:click="$wire.startReview({{ $project->id }})"
                        class="mt-2 flex w-full items-center justify-center gap-1.5 rounded-lg bg-[#FECC39] px-2.5 py-1.5 text-sm font-semibold text-[#272727] transition hover:bg-[#ffe08a]"
                    >
                        <x-filament::icon icon="heroicon-o-eye" class="h-3.5 w-3.5" />
                        Взяти в розгляд
                    </button>
                    <p class="mt-1.5 text-xs leading-snug text-gray-300">Поки в черзі, митець ще може редагувати проєкт. Після взяття в розгляд редагування блокується.</p>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between gap-2.5">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/5">
                        <x-filament::icon icon="heroicon-o-user" class="h-3.5 w-3.5 text-gray-400" />
                    </span>
                    <span class="truncate text-base text-gray-200">{{ $project->user?->display_name }}</span>
                </div>
                @if ($project->user)
                    <button
                        type="button"
                        x-on:click="if (confirm('Увійти на сайт під автором цього проєкту?')) { $wire.impersonateAuthor({{ $project->id }}) }"
                        title="Увійти на фронтенд під цим автором"
                        class="flex shrink-0 items-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-2.5 py-1.5 text-sm font-medium text-gray-300 transition hover:border-[#FECC39]/50 hover:bg-[#FECC39]/10 hover:text-[#FECC39]"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-right-on-rectangle" class="h-3.5 w-3.5" />
                        Увійти як автор
                    </button>
                @endif
            </div>
            <div class="flex items-center gap-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/5">
                    <x-filament::icon icon="heroicon-o-tag" class="h-3.5 w-3.5 text-gray-400" />
                </span>
                <span class="truncate text-base text-gray-200">{{ $project->artCategory?->getLabel('uk') ?? '—' }}</span>
            </div>
            <div class="flex items-start gap-2.5">
                <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/5">
                    <x-filament::icon icon="heroicon-o-banknotes" class="h-3.5 w-3.5 text-gray-400" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="project-view-display text-base text-white">
                        {{ number_format($project->budget_collected, 0, ',', ' ') }} <span class="font-normal text-gray-300">/ {{ number_format($project->budget_goal, 0, ',', ' ') }} {{ $project->currency?->value }}</span>
                    </p>
                    <div class="mt-1.5 flex items-center gap-2">
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-white/10">
                            <div class="project-view-progress h-full rounded-full" style="width: {{ $project->getProgressPercentage() }}%"></div>
                        </div>
                        <span class="shrink-0 text-xs font-semibold tabular-nums text-[#FECC39]">{{ round($project->getProgressPercentage()) }}%</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/5">
                    <x-filament::icon icon="heroicon-o-heart" class="h-3.5 w-3.5 text-gray-400" />
                </span>
                <span class="text-base text-gray-200">{{ $project->donors_count }} меценатів · {{ $project->likes_count }} лайків</span>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/5">
                    <x-filament::icon icon="heroicon-o-calendar" class="h-3.5 w-3.5 text-gray-400" />
                </span>
                <span class="text-base text-gray-200">{{ $project->created_at->format('d.m.Y') }}</span>
            </div>
        </div>
    </div>

    {{-- Main --}}
    <div class="flex min-h-0 min-w-0 flex-1 flex-col">
        {{-- Hero header --}}
        <div class="shrink-0 border-b border-white/5 px-6 pb-5 pt-6">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#FECC39]">{{ $project->artCategory?->getLabel('uk') ?? 'Проєкт' }}</p>
            <h1 class="project-view-display mt-1 text-3xl leading-tight text-white">{{ $title }}</h1>
        </div>

        <div class="flex shrink-0 gap-1 overflow-x-auto border-b border-white/5 px-6">
            @foreach ($tabs as $key => $meta)
                <button
                    type="button"
                    x-on:click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'border-[#FECC39] text-[#FECC39]' : 'border-transparent text-gray-300 hover:text-white'"
                    class="flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-2.5 text-base font-medium transition-colors"
                >
                    <x-filament::icon :icon="$meta['icon']" class="h-4 w-4" />
                    {{ $meta['label'] }}
                </button>
            @endforeach
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto p-6">
            {{-- Tab: General --}}
            <div
                x-show="tab === 'general'"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="space-y-6"
            >
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Ідентифікація</p>
                    <div class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Slug</p>
                            <p class="mt-0.5 text-base text-white">{{ $project->slug }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Тип автора</p>
                            <p class="mt-0.5 text-base text-white">{{ $project->user_type?->getLabel() ?? '—' }}{{ $project->is_legal ? ' (юрособа)' : '' }}</p>
                        </div>
                    </div>
                </div>

                @if ($project->user)
                    <div class="border-t border-white/5 pt-6">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Автор</p>
                        <div class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">ПІБ</p>
                                <p class="mt-0.5 text-base text-white">{{ $project->user->display_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Email</p>
                                <p class="mt-0.5 text-base text-white">{{ $project->user->email }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Телефон</p>
                                <p class="mt-0.5 text-base text-white">{{ $project->user->phone ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Тип профілю</p>
                                <p class="mt-0.5 text-base text-white">{{ $project->user->profile_type?->getLabel() ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Професія</p>
                                <p class="mt-0.5 text-base text-white">{{ $project->user->getLocalized('profession') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Локація</p>
                                <p class="mt-0.5 text-base text-white">
                                    {{ collect([$project->user->getLocalized('country'), $project->user->getLocalized('region'), $project->user->getLocalized('city')])->filter()->implode(', ') ?: '—' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    @if ($project->user->profileLegal)
                        <div class="border-t border-white/5 pt-6">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Юридична особа</p>
                            <div class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Назва</p>
                                    <p class="mt-0.5 text-base text-white">{{ $project->user->profileLegal->getLocalized('name') ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">ЄДРПОУ</p>
                                    <p class="mt-0.5 text-base text-white">{{ $project->user->profileLegal->edrpou ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Уповноважена особа</p>
                                    <p class="mt-0.5 text-base text-white">{{ $project->user->profileLegal->getLocalized('authorized_person') ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Адреса</p>
                                    <p class="mt-0.5 text-base text-white">{{ $project->user->profileLegal->getLocalized('address') ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Телефон</p>
                                    <p class="mt-0.5 text-base text-white">{{ $project->user->profileLegal->phone ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Email</p>
                                    <p class="mt-0.5 text-base text-white">{{ $project->user->profileLegal->email ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Валюта</p>
                                    <p class="mt-0.5 text-base text-white">{{ $project->user->profileLegal->currency?->value ?? '—' }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                <div class="border-t border-white/5 pt-6">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Терміни</p>
                    <div class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Оціночні дні</p>
                            <p class="mt-0.5 text-base text-white">{{ $project->estimated_days ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Оголошено</p>
                            <p class="mt-0.5 text-base text-white">{{ $project->announced_at?->format('d.m.Y H:i') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Заплановане завершення</p>
                            <p class="mt-0.5 text-base text-white">{{ $project->planned_completion_at?->format('d.m.Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Фактичне завершення</p>
                            <p class="mt-0.5 text-base text-white">{{ $project->completed_at?->format('d.m.Y H:i') ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-white/5 pt-6">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Службова інформація</p>
                    <div class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Створено</p>
                            <p class="mt-0.5 text-base text-white">{{ $project->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Оновлено</p>
                            <p class="mt-0.5 text-base text-white">{{ $project->updated_at->format('d.m.Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                @if ($project->rejection_reason || $project->moderation_comment)
                    <div class="rounded-xl border border-rose-400/10 bg-rose-400/[0.06] p-4">
                        @if ($project->rejection_reason)
                            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-rose-400">Причина відхилення</p>
                            <p class="mt-0.5 text-base text-white">{{ $project->rejection_reason }}</p>
                        @endif
                        @if ($project->moderation_comment)
                            <p class="mt-3 text-xs font-semibold uppercase tracking-[0.1em] text-rose-400">Коментар модератора</p>
                            <p class="mt-0.5 text-base text-white">{{ $project->moderation_comment }}</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Tab: Description --}}
            <div
                x-show="tab === 'description'"
                x-cloak
                class="space-y-6"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Назва</p>
                    <p class="project-view-display mt-0.5 text-xl text-white">{{ $project->title ?: '—' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Короткий опис</p>
                    <p class="mt-1.5 whitespace-pre-line text-base leading-relaxed text-gray-200">{{ $project->short_description ?: '—' }}</p>
                </div>

                @if (! empty($project->additional_info['uk']) || ! empty($project->additional_info['en']))
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Додаткова інформація (UK)</p>
                            <p class="mt-1.5 whitespace-pre-line text-base leading-relaxed text-gray-200">{{ $project->additional_info['uk'] ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Додаткова інформація (EN)</p>
                            <p class="mt-1.5 whitespace-pre-line text-base leading-relaxed text-gray-200">{{ $project->additional_info['en'] ?? '—' }}</p>
                        </div>
                    </div>
                @endif

                @if (! empty($project->tags))
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Теги</p>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            @foreach ($tagList($project->tags) as $tag)
                                <span class="inline-flex items-center rounded-full bg-[#FECC39]/10 px-2.5 py-0.5 text-sm font-medium text-[#FECC39] ring-1 ring-inset ring-[#FECC39]/20">{{ $tag }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Tab: Details & Budget --}}
            <div
                x-show="tab === 'details'"
                x-cloak
                class="space-y-6"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                @if (! empty($project->budget_items))
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Деталі бюджету</p>
                        <div class="mt-4 divide-y divide-white/5 overflow-hidden rounded-xl border border-white/5">
                            @foreach ($project->budget_items as $item)
                                <div class="flex items-center justify-between gap-3 bg-white/[0.02] px-4 py-3 text-base even:bg-white/[0.04]">
                                    <span class="block text-white">{{ $item['name'] ?? '—' }}</span>
                                    <span class="project-view-display text-white">{{ number_format($item['amount'] ?? 0, 0, ',', ' ') }} {{ $project->currency?->value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (empty($project->budget_items))
                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-white/10 py-10 text-center">
                        <x-filament::icon icon="heroicon-o-inbox" class="h-6 w-6 text-gray-500" />
                        <p class="mt-2 text-base text-gray-300">Деталей немає</p>
                    </div>
                @endif
            </div>

            {{-- Tab: Characteristics --}}
            <div
                x-show="tab === 'characteristics'"
                x-cloak
                class="space-y-3"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                @forelse ($project->projectParameters as $projectParameter)
                    @php
                        $parameter = $projectParameter->parameter;
                        $valueUk = $projectParameter->parameterValue?->getLabel('uk') ?? $projectParameter->custom_value['uk'] ?? null;
                        $valueEn = $projectParameter->parameterValue?->getLabel('en') ?? $projectParameter->custom_value['en'] ?? null;
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/5 bg-white/[0.03] p-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-white">{{ $parameter?->getLabel('uk') ?? '—' }}</p>
                            @if (! empty($parameter?->getLabel('en')))
                                <p class="text-xs text-gray-500">{{ $parameter->getLabel('en') }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-base text-white">{{ $valueUk ?: '—' }}</p>
                            @if (! empty($valueEn))
                                <p class="text-sm text-gray-400">{{ $valueEn }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-white/10 py-10 text-center">
                        <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="h-6 w-6 text-gray-500" />
                        <p class="mt-2 text-base text-gray-300">Характеристик немає</p>
                    </div>
                @endforelse
            </div>

            {{-- Tab: Content --}}
            <div
                x-show="tab === 'content'"
                x-cloak
                class="space-y-4"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                @if (! empty($project->content_blocks))
                    @foreach ($project->content_blocks as $block)
                        @if (($block['type'] ?? null) === 'heading')
                            @php
                                $headingSizes = ['h1' => 'text-3xl', 'h2' => 'text-2xl', 'h3' => 'text-xl', 'h4' => 'text-lg'];
                                $headingLevel = $block['heading_level'] ?? 'h2';
                            @endphp
                            <{{ $headingLevel }} class="project-view-display {{ $headingSizes[$headingLevel] ?? 'text-2xl' }} text-white">{{ $block['heading_text'] ?? '—' }}</{{ $headingLevel }}>
                        @elseif (($block['type'] ?? null) === 'paragraph')
                            <p class="whitespace-pre-line text-base leading-relaxed text-gray-200">{{ $block['paragraph_text'] ?? '—' }}</p>
                        @elseif (($block['type'] ?? null) === 'image' && ! empty($block['image']))
                            <button
                                type="button"
                                x-on:click="lightbox = { src: '{{ \Illuminate\Support\Facades\Storage::disk('public')->url($block['image']) }}', alt: '{{ $block['image_alt'] ?? '' }}' }"
                                class="group relative block max-h-96 w-fit max-w-full cursor-zoom-in overflow-hidden rounded-xl shadow-lg shadow-black/40 ring-1 ring-white/10"
                            >
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($block['image']) }}"
                                    alt="{{ $block['image_alt'] ?? '' }}"
                                    class="max-h-96 max-w-full object-contain transition duration-700 ease-out group-hover:scale-105"
                                />
                                <span class="pointer-events-none absolute right-2.5 top-2.5 flex h-8 w-8 items-center justify-center rounded-lg bg-black/40 opacity-0 backdrop-blur-sm transition group-hover:opacity-100">
                                    <x-filament::icon icon="heroicon-o-magnifying-glass-plus" class="h-4 w-4 text-white" />
                                </span>
                            </button>
                            @if (! empty($block['image_caption']))
                                <p class="text-center text-sm text-gray-300">{{ $block['image_caption'] }}</p>
                            @endif
                        @endif
                    @endforeach
                @else
                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-white/10 py-10 text-center">
                        <x-filament::icon icon="heroicon-o-newspaper" class="h-6 w-6 text-gray-500" />
                        <p class="mt-2 text-base text-gray-300">Контент-блоків немає</p>
                    </div>
                @endif
            </div>

            {{-- Tab: Stages --}}
            <div
                x-show="tab === 'stages'"
                x-cloak
                class="space-y-3"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                @forelse ($project->stages as $stage)
                    <div class="rounded-xl border border-white/5 bg-white/[0.03] p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/5 text-sm font-semibold text-gray-300">{{ $stage->order }}</span>
                                <div>
                                    <p class="text-base font-semibold text-white">{{ $stage->title ?: '—' }}</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-sm font-semibold ring-1 ring-inset {{ $badgeClasses[$stageStatusColors[$stage->status->value] ?? 'gray'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $dotClasses[$stageStatusColors[$stage->status->value] ?? 'gray'] }}"></span>
                                {{ $stage->status->getLabel() }}
                            </span>
                        </div>

                        @if (! empty($stage->description))
                            <p class="mt-2 whitespace-pre-line text-base leading-relaxed text-gray-300">{{ $stage->description }}</p>
                        @endif

                        <div class="mt-3 grid grid-cols-2 gap-3 border-t border-white/5 pt-3 sm:grid-cols-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Днів</p>
                                <p class="mt-0.5 text-base text-white">{{ $stage->days_planned ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Бюджет план/факт</p>
                                <p class="project-view-display mt-0.5 text-base text-white">{{ number_format($stage->budget_planned, 0, ',', ' ') }} / {{ $stage->budget_actual !== null ? number_format($stage->budget_actual, 0, ',', ' ') : '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Початок</p>
                                <p class="mt-0.5 text-base text-white">{{ $stage->started_at?->format('d.m.Y') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.1em] text-gray-400">Завершено</p>
                                <p class="mt-0.5 text-base text-white">{{ $stage->completed_at?->format('d.m.Y') ?? '—' }}</p>
                            </div>
                        </div>

                        @if (! empty($stage->documents))
                            <div class="mt-3 flex flex-wrap gap-2 border-t border-white/5 pt-3">
                                @foreach ($stage->documents as $document)
                                    @if (! empty($document['file']))
                                        @php $documentUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($document['file']); @endphp
                                        @if (($document['type'] ?? null) === 'photo')
                                            <button
                                                type="button"
                                                x-on:click="lightbox = { src: '{{ $documentUrl }}', alt: '{{ $document['description'] ?? '' }}' }"
                                                class="group relative flex h-16 w-16 shrink-0 cursor-zoom-in items-center justify-center overflow-hidden rounded-lg border border-white/5 ring-1 ring-white/10"
                                                title="{{ $document['description'] ?? 'Фото' }}"
                                            >
                                                <img src="{{ $documentUrl }}" alt="{{ $document['description'] ?? '' }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-110" />
                                                <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 opacity-0 transition group-hover:bg-black/30 group-hover:opacity-100">
                                                    <x-filament::icon icon="heroicon-o-magnifying-glass-plus" class="h-4 w-4 text-white" />
                                                </span>
                                            </button>
                                        @else
                                            <a
                                                href="{{ $documentUrl }}"
                                                target="_blank"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-white/5 bg-white/[0.03] px-2.5 py-1 text-sm font-medium text-[#FECC39] transition hover:border-[#FECC39]/30 hover:bg-[#FECC39]/10"
                                            >
                                                <x-filament::icon icon="heroicon-o-document" class="h-3.5 w-3.5" />
                                                {{ $document['description'] ?? 'Документ' }}
                                            </a>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-white/10 py-10 text-center">
                        <x-filament::icon icon="heroicon-o-list-bullet" class="h-6 w-6 text-gray-500" />
                        <p class="mt-2 text-base text-gray-300">Етапів немає</p>
                    </div>
                @endforelse
            </div>

            {{-- Tab: Bonuses --}}
            <div
                x-show="tab === 'bonuses'"
                x-cloak
                class="space-y-3"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                @forelse ($project->bonuses as $bonus)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/5 bg-white/[0.03] p-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-base font-semibold text-white">{{ $bonus->title ?: '—' }}</p>
                            @if (! empty($bonus->description))
                                <p class="mt-0.5 text-base text-gray-400">{{ $bonus->description }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="project-view-display text-base text-[#FECC39]">
                                {{ number_format($bonus->min_donation, 0, ',', ' ') }}{{ $bonus->max_donation ? ' – '.number_format($bonus->max_donation, 0, ',', ' ') : '+' }} {{ $project->currency?->value }}
                            </p>
                            <p class="mt-0.5 text-sm text-gray-300">{{ $bonus->quantity_claimed }} / {{ $bonus->quantity ?? '∞' }} видано</p>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-white/10 py-10 text-center">
                        <x-filament::icon icon="heroicon-o-gift" class="h-6 w-6 text-gray-500" />
                        <p class="mt-2 text-base text-gray-300">Бонусів немає</p>
                    </div>
                @endforelse
            </div>

            {{-- Tab: Donations --}}
            <div
                x-show="tab === 'donations'"
                x-cloak
                class="space-y-1.5"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                @forelse ($project->donations as $donation)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-white/5 bg-white/[0.03] px-4 py-2.5">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/5">
                                <x-filament::icon icon="heroicon-o-user" class="h-4 w-4 text-gray-400" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-base font-medium text-white">{{ $donation->getDisplayName() }}</p>
                                <p class="truncate text-sm text-gray-300">
                                    {{ $donation->paid_at?->format('d.m.Y H:i') ?? $donation->created_at->format('d.m.Y H:i') }}
                                    @if ($donation->bonus)
                                        · {{ $donation->bonus->title ?: 'Бонус' }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-sm font-semibold ring-1 ring-inset {{ $badgeClasses[$donation->isPaid() ? 'success' : ($donation->isPending() ? 'warning' : 'danger')] }}">
                                {{ \App\Enums\DonationStatus::tryFrom($donation->status)?->getLabel() ?? $donation->status }}
                            </span>
                            <span class="project-view-display text-base text-[#FECC39]">{{ number_format($donation->amount, 0, ',', ' ') }} {{ $donation->currency?->value }}</span>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-white/10 py-10 text-center">
                        <x-filament::icon icon="heroicon-o-heart" class="h-6 w-6 text-gray-500" />
                        <p class="mt-2 text-base text-gray-300">Донатів ще немає</p>
                    </div>
                @endforelse
            </div>

            {{-- Tab: Result --}}
            <div
                x-show="tab === 'result'"
                x-cloak
                class="space-y-4"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
            >
                @if (! empty($project->final_result))
                    @php
                        $finalResultDescription = $project->final_result['description'] ?? null;
                        $finalResultDescriptionUk = is_array($finalResultDescription) ? ($finalResultDescription['uk'] ?? null) : $finalResultDescription;
                        $finalResultDescriptionEn = is_array($finalResultDescription) ? ($finalResultDescription['en'] ?? null) : null;
                    @endphp
                    @if (! empty($finalResultDescriptionUk))
                        <p class="whitespace-pre-line text-base leading-relaxed text-white">{{ $finalResultDescriptionUk }}</p>
                    @endif
                    @if (! empty($finalResultDescriptionEn))
                        <p class="whitespace-pre-line text-base leading-relaxed text-gray-400">{{ $finalResultDescriptionEn }}</p>
                    @endif
                    @if (! empty($finalResultFiles))
                        <div class="flex flex-wrap gap-2">
                            @foreach ($finalResultFiles as $file)
                                @if ($isImageFile($file['original_name'] ?? $file['url'] ?? null))
                                    <button
                                        type="button"
                                        x-on:click="lightbox = { src: '{{ $file['url'] ?? '' }}', alt: '{{ $file['original_name'] ?? '' }}' }"
                                        class="group relative flex h-20 w-20 shrink-0 cursor-zoom-in items-center justify-center overflow-hidden rounded-lg border border-white/5 ring-1 ring-white/10"
                                        title="{{ $file['original_name'] ?? 'Фото' }}"
                                    >
                                        <img src="{{ $file['url'] ?? '' }}" alt="{{ $file['original_name'] ?? '' }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-110" />
                                        <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 opacity-0 transition group-hover:bg-black/30 group-hover:opacity-100">
                                            <x-filament::icon icon="heroicon-o-magnifying-glass-plus" class="h-4 w-4 text-white" />
                                        </span>
                                    </button>
                                @else
                                <a
                                    href="{{ $file['url'] ?? '#' }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-white/5 bg-white/[0.03] px-3 py-1.5 text-base font-medium text-[#FECC39] shadow-sm transition hover:border-[#FECC39]/30 hover:bg-[#FECC39]/10"
                                >
                                    <x-filament::icon icon="heroicon-o-paper-clip" class="h-3.5 w-3.5" />
                                    {{ $file['original_name'] ?? 'Файл' }}
                                </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-white/10 py-10 text-center">
                        <x-filament::icon icon="heroicon-o-sparkles" class="h-6 w-6 text-gray-500" />
                        <p class="mt-2 text-base text-gray-300">Фінального результату ще немає</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Lightbox --}}
    <div
        x-show="lightbox"
        x-cloak
        x-on:keydown.escape.window.capture="if (lightbox) { lightbox = null; $event.stopPropagation() }"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-8 cursor-zoom-out"
        x-on:click="lightbox = null"
    >
        <img
            x-show="lightbox"
            :src="lightbox?.src"
            :alt="lightbox?.alt"
            class="max-h-full max-w-full cursor-default rounded-lg object-contain shadow-2xl"
            x-on:click.stop=""
        />
        <button
            type="button"
            x-on:click.stop="lightbox = null"
            class="absolute right-5 top-5 flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-white transition hover:bg-white/20"
        >
            <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
        </button>
    </div>
</div>
