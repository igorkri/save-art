<x-filament-panels::page>
    <div class="flex h-[calc(100vh-14rem)] min-h-[28rem] overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
    {{-- Список тредів --}}
    <div class="flex w-64 flex-shrink-0 flex-col border-r border-gray-200 dark:border-white/10 sm:w-72">
        @if ($this->availableProjects->isNotEmpty())
            <div class="border-b border-gray-200 p-3 dark:border-white/10">
                <select
                    x-on:change="if ($event.target.value) { $wire.selectThread(parseInt($event.target.value)); $event.target.value = '' }"
                    class="fi-select-input block w-full rounded-lg border-none bg-gray-50 py-1.5 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20"
                >
                    <option value="">{{ __('profile_messages.chat.new_thread_placeholder') }}</option>
                    @foreach ($this->availableProjects as $project)
                        <option value="{{ $project->id }}">{{ $project->title }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="flex-1 overflow-y-auto">
            @forelse ($this->threads as $thread)
                <button
                    type="button"
                    wire:key="thread-item-{{ $thread['project_id'] ?? 'general' }}"
                    wire:click="selectThread({{ $thread['project_id'] ?? 'null' }})"
                    @class([
                        'flex w-full items-start gap-2 border-b border-gray-100 px-3 py-3 text-left transition dark:border-white/5',
                        'bg-primary-50 dark:bg-primary-500/10' => $activeProjectId === $thread['project_id'],
                        'hover:bg-gray-50 dark:hover:bg-white/5' => $activeProjectId !== $thread['project_id'],
                    ])
                >
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $thread['title'] }}
                            </span>
                            @if ($thread['last_message'])
                                <span class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-500">
                                    {{ $thread['last_message']->created_at->diffForHumans(null, true) }}
                                </span>
                            @endif
                        </span>
                        <span class="mt-0.5 flex items-center justify-between gap-2">
                            <span class="truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ $thread['last_message']->content ?? __('profile_messages.chat.empty_thread_list') }}
                            </span>
                            @if ($thread['unread_count'] > 0)
                                <span class="flex-shrink-0 rounded-full bg-danger-500 px-1.5 py-0.5 text-xs font-medium text-white">
                                    {{ $thread['unread_count'] }}
                                </span>
                            @endif
                        </span>
                    </span>
                </button>
            @empty
                <p class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('profile_messages.chat.empty_thread_list') }}
                </p>
            @endforelse
        </div>
    </div>

    {{-- Активний тред --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-white/10">
            <span class="text-base font-semibold text-gray-950 dark:text-white">
                {{ $this->threads->firstWhere('project_id', $activeProjectId)['title'] ?? __('profile_messages.chat.general_thread') }}
            </span>
        </div>

        <div
            wire:key="messages-{{ $activeProjectId ?? 'general' }}-{{ $this->messages->count() }}"
            x-data
            x-init="$nextTick(() => { $el.scrollTop = $el.scrollHeight })"
            wire:poll.5s
            class="flex-1 space-y-3 overflow-y-auto p-4"
        >
            @forelse ($this->messages as $message)
                <div wire:key="message-{{ $message->id }}" @class(['flex', 'justify-end' => ! $message->isFromAdmin(), 'justify-start' => $message->isFromAdmin()])>
                    <div class="max-w-[75%]">
                        <span @class([
                            'block text-xs',
                            'text-right text-primary-600 dark:text-primary-400' => ! $message->isFromAdmin(),
                            'text-gray-500 dark:text-gray-400' => $message->isFromAdmin(),
                        ])>
                            {{ match (true) {
                                $message->isFromSystem() => __('profile_messages.direction.system'),
                                $message->isFromAdmin() => __('profile_messages.direction.admin'),
                                default => __('profile_messages.direction.you'),
                            } }}
                        </span>

                        <div @class([
                            'mt-0.5 whitespace-pre-line break-words rounded-2xl px-3.5 py-2 text-sm',
                            'bg-primary-600 text-white' => ! $message->isFromAdmin(),
                            'bg-gray-100 text-gray-950 dark:bg-white/5 dark:text-white' => $message->isFromAdmin(),
                        ])>
                            {{ $message->content }}
                        </div>

                        <span @class([
                            'mt-0.5 block text-xs text-gray-400 dark:text-gray-500',
                            'text-right' => ! $message->isFromAdmin(),
                        ])>
                            {{ $message->created_at->format('d.m.Y H:i') }}
                        </span>
                    </div>
                </div>
            @empty
                <p class="flex h-full items-center justify-center text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('profile_messages.chat.empty_messages') }}
                </p>
            @endforelse
        </div>

        <form wire:submit.prevent="sendMessage" class="flex items-end gap-2 border-t border-gray-200 p-3 dark:border-white/10">
            <textarea
                wire:model="messageInput"
                x-on:keydown.enter.prevent="if (! $event.shiftKey) { $wire.sendMessage() }"
                rows="1"
                placeholder="{{ __('profile_messages.chat.input_placeholder') }}"
                class="fi-input block w-full flex-1 resize-none rounded-lg border-none bg-gray-50 py-2 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20"
            ></textarea>

            @error('messageInput')
                <span class="text-xs text-danger-600">{{ $message }}</span>
            @enderror

            <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                {{ __('profile_messages.chat.send') }}
            </x-filament::button>
        </form>
    </div>
    </div>
</x-filament-panels::page>
