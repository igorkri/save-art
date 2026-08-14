<x-filament-panels::page>
    <div class="mb-5 max-w-sm">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Пошук за назвою або автором..."
            class="fi-input block w-full rounded-lg border-none bg-white px-3 py-2 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:focus:ring-primary-500"
        />
    </div>

    <div
        x-data="{ draggingId: null }"
        class="flex items-start gap-4 overflow-x-auto pb-4"
    >
        @foreach ($this->getColumns() as $status => $projects)
            @php
                $statusEnum = \App\Enums\ProjectStatus::from($status);
            @endphp

            <div
                x-on:dragover.prevent="true"
                x-on:drop.prevent="if (draggingId) { $wire.moveProject(draggingId, '{{ $status }}'); draggingId = null; }"
                class="flex w-96 shrink-0 flex-col rounded-xl bg-gray-50 dark:bg-white/[0.03] dark:ring-1 dark:ring-white/5"
            >
                <div class="flex items-center justify-between gap-2 border-b border-gray-200 px-5 py-4 dark:border-white/5">
                    <span class="text-base font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">
                        {{ $statusEnum->getLabel() }}
                    </span>
                    <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-gray-200 px-2 text-sm font-semibold text-gray-600 dark:bg-[#FECC39]/10 dark:text-[#FECC39]">
                        {{ $projects->count() }}
                    </span>
                </div>

                <div class="flex flex-col gap-4 p-4 min-h-24">
                    @forelse ($projects as $project)
                        <div
                            draggable="true"
                            x-on:dragstart="draggingId = {{ $project->id }}"
                            x-on:dragend="draggingId = null"
                            class="cursor-move rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 transition hover:shadow-md hover:ring-primary-400 dark:bg-[#1f1f1f] dark:ring-white/10 dark:hover:ring-[#FECC39]/40"
                        >
                            <div class="flex items-start gap-4">
                                @if ($project->cover)
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($project->cover) }}"
                                        alt=""
                                        class="h-16 w-16 shrink-0 rounded-lg object-cover ring-1 ring-gray-950/5 dark:ring-white/10"
                                    />
                                @else
                                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-white/5">
                                        <x-filament::icon icon="heroicon-o-photo" class="h-6 w-6 text-gray-400 dark:text-gray-500" />
                                    </div>
                                @endif

                                <div class="min-w-0 flex-1">
                                    <p class="text-base font-semibold leading-snug text-gray-900 dark:text-white">
                                        {{ $project->title ?: '—' }}
                                    </p>
                                    <p class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400">
                                        {{ $project->user?->display_name }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                                    <div
                                        class="h-full rounded-full bg-primary-500 dark:bg-[#FECC39]"
                                        style="width: {{ min(100, $project->getProgressPercentage()) }}%"
                                    ></div>
                                </div>
                                <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {{ number_format($project->budget_collected, 0, ',', ' ') }} <span class="text-gray-400 dark:text-gray-500">/ {{ number_format($project->budget_goal, 0, ',', ' ') }} {{ $project->currency?->value }}</span>
                                </p>
                            </div>

                            <div class="mt-4 flex items-center justify-between gap-2 border-t border-gray-100 pt-3 dark:border-white/5">
                                <button
                                    type="button"
                                    wire:click="mountAction('view', { project: {{ $project->id }} })"
                                    class="text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-[#FECC39] dark:hover:text-[#ffe08a]"
                                >
                                    Переглянути →
                                </button>

                                @if ($project->user)
                                    <div class="flex shrink-0 items-center gap-2">
                                        <button
                                            type="button"
                                            x-on:click.stop="if (confirm('Увійти на save-art під автором цього проєкту?')) { $wire.impersonateAuthor({{ $project->id }}) }"
                                            title="Увійти на save-art під цим автором"
                                            class="text-gray-400 transition hover:text-primary-500 dark:text-gray-500 dark:hover:text-[#FECC39]"
                                        >
                                            <x-filament::icon icon="heroicon-o-arrow-right-on-rectangle" class="h-5 w-5" />
                                        </button>
                                        <button
                                            type="button"
                                            x-on:click.stop="if (confirm('Увійти на art-ua-info під автором цього проєкту?')) { $wire.impersonateAuthorArtUaInfo({{ $project->id }}) }"
                                            title="Увійти на art-ua-info під цим автором"
                                            class="text-gray-400 transition hover:text-primary-500 dark:text-gray-500 dark:hover:text-[#FECC39]"
                                        >
                                            <x-filament::icon icon="heroicon-o-globe-alt" class="h-5 w-5" />
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 py-10 text-center dark:border-white/10">
                            <x-filament::icon icon="heroicon-o-inbox" class="h-6 w-6 text-gray-300 dark:text-gray-600" />
                            <p class="mt-2 text-sm text-gray-400 dark:text-gray-500">Немає проєктів</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
