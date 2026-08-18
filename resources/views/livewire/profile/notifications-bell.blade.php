<div wire:poll.30s="refreshUnreadCount" class="fi-notifications-bell flex items-center">
    {{-- wire:key на дропдауні вмикає wire:ignore.self у компоненті Filament,
    тож панель не втрачає стан "відкрито" й не закривається щоразу, коли
    клік всередині (наприклад, позначення прочитаним) робить Livewire-запит. --}}
    <x-filament::dropdown placement="bottom-end" width="sm" shift wire:key="notifications-bell-panel">
        <x-slot name="trigger">
            <x-filament::icon-button
                icon="heroicon-o-bell"
                icon-size="lg"
                color="gray"
                :badge="$unreadCount > 0 ? ($unreadCount > 9 ? '9+' : $unreadCount) : null"
                badge-color="danger"
                :label="__('profile_notifications.bell.label')"
            />
        </x-slot>

        <div class="flex items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-white/10">
            <span class="text-base font-semibold text-gray-950 dark:text-white">
                {{ __('profile_notifications.model.plural') }}
            </span>

            @if ($unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllAsRead"
                    class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400"
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

                {{-- div замість button: усередині є посилання "Переглянути проєкт",
                а вкладати <a> у <button> заборонено специфікацією HTML. --}}
                <div
                    role="button"
                    tabindex="0"
                    wire:key="notification-{{ $notification->id }}"
                    x-data="{ expanded: false }"
                    x-on:click="expanded = ! expanded; $wire.markAsRead({{ $notification->id }})"
                    x-on:keydown.enter="expanded = ! expanded; $wire.markAsRead({{ $notification->id }})"
                    @class([
                        'flex w-full cursor-pointer items-start gap-3 border-b border-gray-100 px-4 py-3 text-left transition hover:bg-gray-50 last:border-b-0 dark:border-white/5 dark:hover:bg-white/5',
                        'bg-primary-50/50 dark:bg-primary-500/5' => ! $notification->is_read,
                    ])
                >
                    <x-filament::icon
                        :icon="$notification->type->getIcon()"
                        :color="$notification->type->getColor()"
                        class="mt-0.5 h-5 w-5 flex-shrink-0"
                    />

                    <span class="min-w-0 flex-1">
                        <span @class(['block text-base text-gray-950 dark:text-white', 'font-semibold' => ! $notification->is_read])>
                            {{ $title }}
                        </span>
                        @if ($message)
                            <span
                                class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400"
                                x-bind:class="expanded ? 'whitespace-pre-line' : 'line-clamp-2'"
                            >
                                {{ $message }}
                            </span>
                        @endif
                        <span class="mt-1 block text-sm text-gray-400 dark:text-gray-500">
                            {{ $notification->created_at->diffForHumans() }}
                        </span>

                        @if ($projectSlug = $notification->data['project_slug'] ?? null)
                            <a
                                href="{{ rtrim(config('app.frontend_url'), '/') }}/projects/{{ $projectSlug }}"
                                target="_blank"
                                rel="noopener"
                                x-on:click.stop
                                class="mt-1 inline-block text-sm font-medium text-primary-600 hover:underline dark:text-primary-400"
                            >
                                {{ __('profile_notifications.bell.view_project') }}
                            </a>
                        @endif
                    </span>

                    @if (! $notification->is_read)
                        <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-primary-500"></span>
                    @endif
                </div>
            @empty
                <p class="px-4 py-6 text-center text-base text-gray-500 dark:text-gray-400">
                    {{ __('profile_notifications.bell.empty') }}
                </p>
            @endforelse
        </div>

        <a
            href="{{ $this->indexUrl }}"
            class="block border-t border-gray-200 px-4 py-2.5 text-center text-base font-medium text-primary-600 hover:underline dark:border-white/10 dark:text-primary-400"
        >
            {{ __('profile_notifications.bell.view_all') }}
        </a>
    </x-filament::dropdown>
</div>
