<x-filament-panels::page>
    <div class="mb-4 max-w-sm">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Пошук за назвою або автором..."
            class="fi-input block w-full rounded-lg border-none bg-white px-3 py-1.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:focus:ring-primary-500"
        />
    </div>

    <div
        x-data="{ draggingId: null }"
        class="flex gap-4 overflow-x-auto pb-4"
    >
        @foreach ($this->getColumns() as $status => $projects)
            @php
                $statusEnum = \App\Enums\ProjectStatus::from($status);
            @endphp

            <div
                x-on:dragover.prevent="true"
                x-on:drop.prevent="if (draggingId) { $wire.moveProject(draggingId, '{{ $status }}'); draggingId = null; }"
                class="flex w-72 shrink-0 flex-col rounded-xl bg-gray-50 dark:bg-gray-800"
            >
                <div class="flex items-center justify-between px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        {{ $statusEnum->getLabel() }}
                    </span>
                    <span class="inline-flex items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-300">
                        {{ $projects->count() }}
                    </span>
                </div>

                <div class="flex flex-col gap-2 p-2 min-h-24">
                    @forelse ($projects as $project)
                        <div
                            draggable="true"
                            x-on:dragstart="draggingId = {{ $project->id }}"
                            x-on:dragend="draggingId = null"
                            class="cursor-move rounded-lg bg-white dark:bg-gray-900 p-3 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 hover:ring-primary-400 transition"
                        >
                            <div class="flex items-start gap-2">
                                @if ($project->cover)
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($project->cover) }}"
                                        alt=""
                                        class="h-10 w-10 shrink-0 rounded-full object-cover"
                                    />
                                @endif

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $project->title['uk'] ?? $project->title['en'] ?? '—' }}
                                    </p>
                                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ $project->user?->display_name }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-2">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div
                                        class="h-full rounded-full bg-primary-500"
                                        style="width: {{ $project->getProgressPercentage() }}%"
                                    ></div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($project->budget_collected, 0, ',', ' ') }} / {{ number_format($project->budget_goal, 0, ',', ' ') }} {{ $project->currency?->value }}
                                </p>
                            </div>

                            <button
                                type="button"
                                wire:click="mountAction('view', { project: {{ $project->id }} })"
                                class="mt-2 text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                            >
                                Переглянути →
                            </button>
                        </div>
                    @empty
                        <p class="px-1 py-4 text-center text-xs text-gray-400">Немає проєктів</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
