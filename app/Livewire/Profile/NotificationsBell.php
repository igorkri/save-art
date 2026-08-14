<?php

namespace App\Livewire\Profile;

use App\Filament\Profile\Resources\Notifications\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class NotificationsBell extends Component
{
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->refreshUnreadCount();
    }

    /**
     * @return Collection<int, Notification>
     */
    #[Computed]
    public function notifications(): Collection
    {
        return Notification::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(8)
            ->get();
    }

    public function refreshUnreadCount(): void
    {
        $this->unreadCount = app(NotificationService::class)->getUnreadCount(auth()->user());
    }

    public function markAsRead(int $notificationId): void
    {
        Notification::query()
            ->where('user_id', auth()->id())
            ->whereKey($notificationId)
            ->first()
            ?->markAsRead();

        unset($this->notifications);
        $this->refreshUnreadCount();
    }

    public function markAllAsRead(): void
    {
        app(NotificationService::class)->markAllAsRead(auth()->user());

        unset($this->notifications);
        $this->refreshUnreadCount();
    }

    public function getIndexUrlProperty(): string
    {
        return NotificationResource::getUrl('index');
    }

    public function render(): View
    {
        return view('livewire.profile.notifications-bell');
    }
}
