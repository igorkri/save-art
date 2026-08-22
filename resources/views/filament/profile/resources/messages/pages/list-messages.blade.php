<x-filament-panels::page>
    <section class="profile-messages-chat" aria-label="{{ __('profile_messages.model.plural') }}">
        {{-- Список діалогів --}}
        <aside class="profile-messages-chat__sidebar">
            <header class="profile-messages-chat__sidebar-header">
                <span class="profile-messages-chat__eyebrow">{{ __('profile_messages.chat.support_label') }}</span>
                <div class="profile-messages-chat__sidebar-title">
                    <h2>{{ __('profile_messages.chat.conversations') }}</h2>
                    <span>{{ $this->threads->count() }}</span>
                </div>
            </header>

            @if ($this->availableProjects->isNotEmpty())
                <div class="profile-messages-chat__project-picker">
                    <label for="message-project">{{ __('profile_messages.chat.new_thread_label') }}</label>
                    <div class="profile-messages-chat__select-wrap">
                        <x-filament::icon icon="heroicon-o-plus" aria-hidden="true" />
                        <select
                            id="message-project"
                            x-on:change="if ($event.target.value) { $wire.selectThread(parseInt($event.target.value)); $event.target.value = '' }"
                        >
                            <option value="">{{ __('profile_messages.chat.new_thread_placeholder') }}</option>
                            @foreach ($this->availableProjects as $project)
                                <option value="{{ $project->id }}">{{ $project->title }}</option>
                            @endforeach
                        </select>
                        <x-filament::icon icon="heroicon-o-chevron-down" aria-hidden="true" />
                    </div>
                </div>
            @endif

            <nav class="profile-messages-chat__threads" aria-label="{{ __('profile_messages.chat.conversations') }}">
                @forelse ($this->threads as $thread)
                    <button
                        type="button"
                        wire:key="thread-item-{{ $thread['project_id'] ?? 'general' }}"
                        wire:click="selectThread({{ $thread['project_id'] ?? 'null' }})"
                        @class([
                            'profile-messages-chat__thread',
                            'is-active' => $activeProjectId === $thread['project_id'],
                        ])
                        @if ($activeProjectId === $thread['project_id']) aria-current="page" @endif
                    >
                        <span class="profile-messages-chat__thread-icon" aria-hidden="true">
                            <x-filament::icon :icon="$thread['project_id'] ? 'heroicon-o-photo' : 'heroicon-o-chat-bubble-left-right'" />
                        </span>

                        <span class="profile-messages-chat__thread-body">
                            <span class="profile-messages-chat__thread-head">
                                <strong>{{ $thread['title'] }}</strong>
                                @if ($thread['last_message'])
                                    <time datetime="{{ $thread['last_message']->created_at->toIso8601String() }}">
                                        {{ $thread['last_message']->created_at->diffForHumans(null, true) }}
                                    </time>
                                @endif
                            </span>
                            <span class="profile-messages-chat__thread-preview">
                                <span>{{ $thread['last_message']->content ?? __('profile_messages.chat.empty_thread_list') }}</span>
                                @if ($thread['unread_count'] > 0)
                                    <b aria-label="{{ $thread['unread_count'] }}">{{ $thread['unread_count'] }}</b>
                                @endif
                            </span>
                        </span>
                    </button>
                @empty
                    <div class="profile-messages-chat__empty-threads">
                        <x-filament::icon icon="heroicon-o-chat-bubble-left-ellipsis" aria-hidden="true" />
                        <p>{{ __('profile_messages.chat.empty_thread_list') }}</p>
                    </div>
                @endforelse
            </nav>
        </aside>

        {{-- Активний діалог --}}
        <div class="profile-messages-chat__conversation">
            <header class="profile-messages-chat__conversation-header">
                <span class="profile-messages-chat__conversation-icon" aria-hidden="true">
                    <x-filament::icon :icon="$activeProjectId ? 'heroicon-o-photo' : 'heroicon-o-chat-bubble-left-right'" />
                </span>
                <div>
                    <span>{{ $activeProjectId ? __('profile_messages.table.project') : __('profile_messages.chat.support_label') }}</span>
                    <h2>{{ $this->threads->firstWhere('project_id', $activeProjectId)['title'] ?? __('profile_messages.chat.general_thread') }}</h2>
                </div>
                <span class="profile-messages-chat__status">
                    <i aria-hidden="true"></i>
                    {{ __('profile_messages.chat.available') }}
                </span>
            </header>

            <div
                wire:key="messages-{{ $activeProjectId ?? 'general' }}-{{ $this->messages->count() }}"
                x-data
                x-init="$nextTick(() => { $el.scrollTop = $el.scrollHeight })"
                wire:poll.5s
                class="profile-messages-chat__messages"
                aria-live="polite"
            >
                @forelse ($this->messages as $message)
                    <article
                        wire:key="message-{{ $message->id }}"
                        @class([
                            'profile-messages-chat__message',
                            'is-outgoing' => ! $message->isFromAdmin(),
                            'is-incoming' => $message->isFromAdmin(),
                            'is-system' => $message->isFromSystem(),
                        ])
                    >
                        @if ($message->isFromAdmin())
                            <span class="profile-messages-chat__avatar" aria-hidden="true">
                                <x-filament::icon :icon="$message->isFromSystem() ? 'heroicon-o-sparkles' : 'heroicon-o-building-office-2'" />
                            </span>
                        @endif

                        <div class="profile-messages-chat__message-content">
                            <span class="profile-messages-chat__author">
                                {{ match (true) {
                                    $message->isFromSystem() => __('profile_messages.direction.system'),
                                    $message->isFromAdmin() => __('profile_messages.direction.admin'),
                                    default => __('profile_messages.direction.you'),
                                } }}
                            </span>
                            <p>{{ $message->content }}</p>
                            <time datetime="{{ $message->created_at->toIso8601String() }}">
                                {{ $message->created_at->format('d.m.Y, H:i') }}
                            </time>
                        </div>
                    </article>
                @empty
                    <div class="profile-messages-chat__empty-messages">
                        <span aria-hidden="true">
                            <x-filament::icon icon="heroicon-o-paper-airplane" />
                        </span>
                        <h3>{{ __('profile_messages.chat.empty_title') }}</h3>
                        <p>{{ __('profile_messages.chat.empty_messages') }}</p>
                    </div>
                @endforelse
            </div>

            <form wire:submit.prevent="sendMessage" class="profile-messages-chat__composer">
                <div class="profile-messages-chat__input-wrap">
                    <textarea
                        wire:model="messageInput"
                        x-data
                        x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 144) + 'px'"
                        x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $wire.sendMessage(); $el.style.height = 'auto' }"
                        rows="1"
                        maxlength="5000"
                        aria-label="{{ __('profile_messages.chat.input_placeholder') }}"
                        placeholder="{{ __('profile_messages.chat.input_placeholder') }}"
                    ></textarea>
                    <small>{{ __('profile_messages.chat.input_hint') }}</small>
                </div>

                <button type="submit" class="profile-messages-chat__send">
                    <span>{{ __('profile_messages.chat.send') }}</span>
                    <x-filament::icon icon="heroicon-o-paper-airplane" aria-hidden="true" />
                </button>

                @error('messageInput')
                    <p class="profile-messages-chat__error">{{ $message }}</p>
                @enderror
            </form>
        </div>
    </section>
</x-filament-panels::page>
