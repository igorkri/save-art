<div wire:poll.30s="refreshUnreadCount">
    <x-filament::dropdown placement="bottom-end" width="sm">
        <x-slot name="trigger">
            <button
                type="button"
                class="fi-icon-btn relative flex h-9 w-9 items-center justify-center rounded-full text-gray-500 outline-none transition duration-75 hover:bg-gray-100 hover:text-gray-700 focus-visible:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200 dark:focus-visible:bg-white/5"
                aria-label="{{ __('profile_notifications.bell.label') }}"
            >
                <x-filament::icon icon="heroicon-o-bell" class="h-6 w-6" />

                @if ($unreadCount > 0)
                    <span class="absolute -top-0.5 -right-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-danger-500 px-1 text-[10px] font-bold leading-none text-white">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif
            </button>
        </x-slot>

        <div class="flex items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-white/10">
            <span class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ __('profile_notifications.model.plural') }}
            </span>

            @if ($unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllAsRead"
                    class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400"
                >
                    {{ __('profile_notifications.actions.mark_all_as_read') }}
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($this->notifications as $notification)
                @php
                    $title = $notification->title[app()->getLocale()] ?? $notification->title['uk'] ?? '';
                    $message = $notification->message[app()->getLocale()] ?? $notification->message['uk'] ?? '';
                @endphp

                <button
                    type="button"
                    wire:click="markAsRead({{ $notification->id }})"
                    @class([
                        'flex w-full items-start gap-3 border-b border-gray-100 px-4 py-3 text-left transition hover:bg-gray-50 last:border-b-0 dark:border-white/5 dark:hover:bg-white/5',
                        'bg-primary-50/50 dark:bg-primary-500/5' => ! $notification->is_read,
                    ])
                >
                    <x-filament::icon
                        :icon="$notification->type->getIcon()"
                        :color="$notification->type->getColor()"
                        class="mt-0.5 h-5 w-5 flex-shrink-0"
                    />

                    <span class="min-w-0 flex-1">
                        <span @class(['block text-sm text-gray-950 dark:text-white', 'font-semibold' => ! $notification->is_read])>
                            {{ $title }}
                        </span>
                        @if ($message)
                            <span class="mt-0.5 block truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ $message }}
                            </span>
                        @endif
                        <span class="mt-1 block text-xs text-gray-400 dark:text-gray-500">
                            {{ $notification->created_at->diffForHumans() }}
                        </span>
                    </span>

                    @if (! $notification->is_read)
                        <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-primary-500"></span>
                    @endif
                </button>
            @empty
                <p class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('profile_notifications.bell.empty') }}
                </p>
            @endforelse
        </div>

        <a
            href="{{ $this->indexUrl }}"
            class="block border-t border-gray-200 px-4 py-2.5 text-center text-sm font-medium text-primary-600 hover:underline dark:border-white/10 dark:text-primary-400"
        >
            {{ __('profile_notifications.bell.view_all') }}
        </a>
    </x-filament::dropdown>
</div>
