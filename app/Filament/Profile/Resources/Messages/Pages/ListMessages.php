<?php

namespace App\Filament\Profile\Resources\Messages\Pages;

use App\Filament\Profile\Resources\Messages\MessageResource;
use App\Models\Message;
use App\Models\Project;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;

class ListMessages extends Page
{
    protected static string $resource = MessageResource::class;

    protected string $view = 'filament.profile.resources.messages.pages.list-messages';

    public ?int $activeProjectId = null;

    public string $messageInput = '';

    public function mount(): void
    {
        $firstThread = $this->threads()->first();

        $this->activeProjectId = $firstThread['project_id'] ?? null;
    }

    public function getTitle(): string
    {
        return __('profile_messages.model.plural');
    }

    public function getBreadcrumb(): string
    {
        return __('profile_messages.model.plural');
    }

    /**
     * Треди листування: "Загальні" (без проєкту) завжди першим пунктом,
     * далі — проєкти, за якими вже є повідомлення, від найновішого.
     *
     * @return SupportCollection<int, array{project_id: ?int, title: string, last_message: ?Message, unread_count: int}>
     */
    #[Computed]
    public function threads(): SupportCollection
    {
        $messages = Message::query()
            ->where('user_id', auth()->id())
            ->with('project')
            ->orderByDesc('created_at')
            ->get();

        $grouped = $messages->groupBy('project_id');

        $threads = $grouped->map(function (Collection $group, $projectId) {
            $projectId = $projectId !== '' ? (int) $projectId : null;

            return [
                'project_id' => $projectId,
                'title' => $projectId ? ($group->first()->project?->title ?? "#{$projectId}") : __('profile_messages.chat.general_thread'),
                'last_message' => $group->first(),
                'unread_count' => $group->filter(fn (Message $message) => $message->isFromAdmin() && ! $message->isRead())->count(),
            ];
        })->values();

        if (! $threads->contains(fn (array $thread) => $thread['project_id'] === null)) {
            $threads->push([
                'project_id' => null,
                'title' => __('profile_messages.chat.general_thread'),
                'last_message' => null,
                'unread_count' => 0,
            ]);
        }

        return $threads->sort(function (array $a, array $b) {
            if (($a['project_id'] === null) !== ($b['project_id'] === null)) {
                return $a['project_id'] === null ? -1 : 1;
            }

            return ($b['last_message']?->created_at?->timestamp ?? 0) <=> ($a['last_message']?->created_at?->timestamp ?? 0);
        })->values();
    }

    /**
     * Проєкти користувача, за якими ще не починалось листування — для стартового вибору нового треду.
     *
     * @return Collection<int, Project>
     */
    #[Computed]
    public function availableProjects(): Collection
    {
        $threadedProjectIds = $this->threads()
            ->pluck('project_id')
            ->filter()
            ->values();

        return Project::query()
            ->where('user_id', auth()->id())
            ->whereNotIn('id', $threadedProjectIds)
            ->orderBy('title')
            ->get();
    }

    /**
     * @return Collection<int, Message>
     */
    #[Computed]
    public function messages(): Collection
    {
        $messages = Message::query()
            ->where('user_id', auth()->id())
            ->when(
                $this->activeProjectId !== null,
                fn ($query) => $query->where('project_id', $this->activeProjectId),
                fn ($query) => $query->whereNull('project_id'),
            )
            ->orderBy('created_at')
            ->get();

        $messages
            ->filter(fn (Message $message) => $message->isFromAdmin() && ! $message->isRead())
            ->each(fn (Message $message) => $message->markAsRead());

        return $messages;
    }

    public function selectThread(?int $projectId): void
    {
        $this->activeProjectId = $projectId;
        $this->messageInput = '';

        unset($this->messages);
    }

    public function sendMessage(): void
    {
        $data = $this->validate([
            'messageInput' => ['required', 'string', 'max:5000'],
        ]);

        Message::create([
            'user_id' => auth()->id(),
            'project_id' => $this->activeProjectId,
            'content' => $data['messageInput'],
            'direction' => Message::DIRECTION_USER_TO_ADMIN,
        ]);

        $this->messageInput = '';

        unset($this->threads, $this->availableProjects, $this->messages);
    }
}
