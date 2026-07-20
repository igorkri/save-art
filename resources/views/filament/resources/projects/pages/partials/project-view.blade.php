@php
    $tagList = function (array|string|null $tags) {
        $tags = is_array($tags) ? $tags : explode(',', (string) $tags);

        return collect($tags)->map(fn ($tag) => trim((string) $tag))->filter();
    };

    $statusColors = [
        'new' => 'gray',
        'draft' => 'gray',
        'moderation' => 'warning',
        'announced' => 'info',
        'in_progress' => 'primary',
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
        'warning' => 'bg-amber-500',
        'info' => 'bg-sky-500',
        'primary' => 'bg-primary-500',
        'success' => 'bg-emerald-500',
        'danger' => 'bg-rose-500',
    ];

    $textClasses = [
        'gray' => 'text-gray-600 dark:text-gray-300',
        'warning' => 'text-amber-600 dark:text-amber-400',
        'info' => 'text-sky-600 dark:text-sky-400',
        'primary' => 'text-primary-600 dark:text-primary-400',
        'success' => 'text-emerald-600 dark:text-emerald-400',
        'danger' => 'text-rose-600 dark:text-rose-400',
    ];

    $finalResultFiles = ! empty($project->final_result)
        ? ($project->final_result['files'] ?? array_filter([$project->final_result['file'] ?? null]))
        : [];
@endphp

<div x-data="{ tab: 'general' }" class="-m-6 flex h-full flex-col lg:flex-row">
    {{-- Sidebar --}}
    <div class="flex w-full shrink-0 flex-col gap-6 overflow-y-auto border-b border-gray-100 bg-gray-50/60 p-6 dark:border-gray-800 dark:bg-white/[0.02] lg:w-72 lg:border-b-0 lg:border-r">
        @if ($project->cover)
            <img
                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($project->cover) }}"
                alt=""
                class="h-32 w-full rounded-xl object-cover shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10"
            />
        @endif

        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ $project->code }}</p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Статус</p>
            <div class="mt-1.5 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full {{ $dotClasses[$statusColors[$project->status->value]] }}"></span>
                <span class="text-sm font-medium {{ $textClasses[$statusColors[$project->status->value]] }}">{{ $project->status->getLabel() }}</span>
            </div>
            <select
                x-on:change="$wire.moveProject({{ $project->id }}, $event.target.value)"
                class="mt-2 w-full rounded-lg border-none bg-white dark:bg-white/5 py-1.5 pl-2.5 pr-8 text-xs font-medium text-gray-700 dark:text-gray-200 shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20 focus:ring-2 focus:ring-primary-600"
            >
                @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected($value === $project->status->value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Модерація</p>
            <div class="mt-1.5 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full {{ $dotClasses[$moderationColors[$project->status_moderation->value]] }}"></span>
                <span class="text-sm font-medium {{ $textClasses[$moderationColors[$project->status_moderation->value]] }}">{{ $project->status_moderation->getLabel() }}</span>
            </div>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700"></div>

        <div class="space-y-4">
            <div class="flex items-center gap-2.5">
                <x-filament::icon icon="heroicon-o-user" class="h-4 w-4 shrink-0 text-gray-400" />
                <span class="truncate text-sm text-gray-700 dark:text-gray-200">{{ $project->user?->display_name }}</span>
            </div>
            <div class="flex items-center gap-2.5">
                <x-filament::icon icon="heroicon-o-tag" class="h-4 w-4 shrink-0 text-gray-400" />
                <span class="truncate text-sm text-gray-700 dark:text-gray-200">{{ $project->artCategory?->getLabel('uk') ?? '—' }}</span>
            </div>
            <div class="flex items-start gap-2.5">
                <x-filament::icon icon="heroicon-o-banknotes" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-gray-700 dark:text-gray-200">
                        {{ number_format($project->budget_collected, 0, ',', ' ') }} / {{ number_format($project->budget_goal, 0, ',', ' ') }} {{ $project->currency?->value }}
                    </p>
                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-full rounded-full bg-primary-500" style="width: {{ $project->getProgressPercentage() }}%"></div>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <x-filament::icon icon="heroicon-o-heart" class="h-4 w-4 shrink-0 text-gray-400" />
                <span class="text-sm text-gray-700 dark:text-gray-200">{{ $project->donors_count }} меценатів · {{ $project->likes_count }} лайків</span>
            </div>
            <div class="flex items-center gap-2.5">
                <x-filament::icon icon="heroicon-o-calendar" class="h-4 w-4 shrink-0 text-gray-400" />
                <span class="text-sm text-gray-700 dark:text-gray-200">{{ $project->created_at->format('d.m.Y') }}</span>
            </div>
        </div>
    </div>

    {{-- Main --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <div class="flex shrink-0 gap-1 border-b border-gray-100 px-6 dark:border-gray-800">
            <button type="button" x-on:click="tab = 'general'" :class="tab === 'general' ? 'border-primary-600 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="border-b-2 px-3 py-2.5 text-sm font-medium transition">Загальне</button>
            <button type="button" x-on:click="tab = 'description'" :class="tab === 'description' ? 'border-primary-600 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="border-b-2 px-3 py-2.5 text-sm font-medium transition">Опис і теги</button>
            <button type="button" x-on:click="tab = 'details'" :class="tab === 'details' ? 'border-primary-600 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="border-b-2 px-3 py-2.5 text-sm font-medium transition">Деталі та бюджет</button>
            <button type="button" x-on:click="tab = 'result'" :class="tab === 'result' ? 'border-primary-600 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="border-b-2 px-3 py-2.5 text-sm font-medium transition">Результат</button>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
            {{-- Tab: General --}}
            <div x-show="tab === 'general'" class="grid grid-cols-1 gap-x-8 gap-y-4 md:grid-cols-2">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Slug</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $project->slug }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Тип автора</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $project->user_type }}{{ $project->is_legal ? ' (юрособа)' : '' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Оціночні дні</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $project->estimated_days ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Оновлено</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $project->updated_at->format('d.m.Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Оголошено</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $project->announced_at?->format('d.m.Y H:i') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Заплановане завершення</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $project->planned_completion_at?->format('d.m.Y') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Фактичне завершення</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $project->completed_at?->format('d.m.Y H:i') ?? '—' }}</p>
                </div>

                @if ($project->rejection_reason || $project->moderation_comment)
                    <div class="md:col-span-2 rounded-lg bg-rose-50 dark:bg-rose-500/10 p-3">
                        @if ($project->rejection_reason)
                            <p class="text-xs font-medium text-rose-600 dark:text-rose-400">Причина відхилення</p>
                            <p class="text-sm text-gray-900 dark:text-white">{{ $project->rejection_reason }}</p>
                        @endif
                        @if ($project->moderation_comment)
                            <p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400">Коментар модератора</p>
                            <p class="text-sm text-gray-900 dark:text-white">{{ $project->moderation_comment }}</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Tab: Description --}}
            <div x-show="tab === 'description'" x-cloak class="space-y-5">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Назва (UK)</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $project->title['uk'] ?? '—' }}</p>
                    <p class="mt-2 text-xs font-medium text-gray-500 dark:text-gray-400">Назва (EN)</p>
                    <p class="text-sm text-gray-900 dark:text-white">{{ $project->title['en'] ?? '—' }}</p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Короткий опис (UK)</p>
                        <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ $project->short_description['uk'] ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Короткий опис (EN)</p>
                        <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ $project->short_description['en'] ?? '—' }}</p>
                    </div>
                </div>

                @if (! empty($project->additional_info['uk']) || ! empty($project->additional_info['en']))
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Додаткова інформація (UK)</p>
                            <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ $project->additional_info['uk'] ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Додаткова інформація (EN)</p>
                            <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ $project->additional_info['en'] ?? '—' }}</p>
                        </div>
                    </div>
                @endif

                @if (! empty($project->tags['uk']) || ! empty($project->tags['en']))
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Теги (UK)</p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            @foreach ($tagList($project->tags['uk'] ?? null) as $tag)
                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs text-gray-700 dark:text-gray-300">{{ $tag }}</span>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs font-medium text-gray-500 dark:text-gray-400">Теги (EN)</p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            @foreach ($tagList($project->tags['en'] ?? null) as $tag)
                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs text-gray-700 dark:text-gray-300">{{ $tag }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Tab: Details & Budget --}}
            <div x-show="tab === 'details'" x-cloak class="space-y-5">
                @if (! empty($project->characteristics))
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Характеристики</p>
                        <div class="mt-1 divide-y divide-gray-100 dark:divide-gray-700 rounded-lg border border-gray-100 dark:border-gray-700">
                            @foreach ($project->characteristics as $item)
                                <div class="flex items-center justify-between gap-3 px-3 py-1.5 text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">{{ $item['name']['uk'] ?? '—' }}</span>
                                    <span class="text-gray-900 dark:text-white">{{ $item['value']['uk'] ?? '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (! empty($project->budget_items))
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Деталі бюджету</p>
                        <div class="mt-1 divide-y divide-gray-100 dark:divide-gray-700 rounded-lg border border-gray-100 dark:border-gray-700">
                            @foreach ($project->budget_items as $item)
                                <div class="flex items-center justify-between gap-3 px-3 py-1.5 text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">{{ $item['name']['uk'] ?? '—' }}</span>
                                    <span class="text-gray-900 dark:text-white">{{ number_format($item['amount'] ?? 0, 0, ',', ' ') }} {{ $project->currency?->value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (empty($project->characteristics) && empty($project->budget_items))
                    <p class="text-sm text-gray-400">Деталей немає</p>
                @endif
            </div>

            {{-- Tab: Result --}}
            <div x-show="tab === 'result'" x-cloak class="space-y-3">
                @if (! empty($project->final_result))
                    @if (! empty($project->final_result['description']))
                        <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ $project->final_result['description'] }}</p>
                    @endif
                    @if (! empty($finalResultFiles))
                        <div class="flex flex-wrap gap-2">
                            @foreach ($finalResultFiles as $file)
                                <a
                                    href="{{ $file['url'] ?? '#' }}"
                                    target="_blank"
                                    class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                                >
                                    {{ $file['original_name'] ?? 'Файл' }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                @else
                    <p class="text-sm text-gray-400">Фінального результату ще немає</p>
                @endif
            </div>
        </div>
    </div>
</div>
