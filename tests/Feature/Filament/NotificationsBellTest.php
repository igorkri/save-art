<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Notifications\NotificationResource;
use App\Livewire\Profile\NotificationsBell;
use App\Models\Notification;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class NotificationsBellTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->artist()->create();

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_bell_shows_unread_count_for_current_user_only(): void
    {
        $otherUser = User::factory()->artist()->create();

        Notification::factory()->count(2)->create(['user_id' => $this->user->id, 'read_at' => null]);
        Notification::factory()->create(['user_id' => $this->user->id, 'read_at' => now()]);
        Notification::factory()->count(5)->create(['user_id' => $otherUser->id, 'read_at' => null]);

        Livewire::test(NotificationsBell::class)
            ->assertSet('unreadCount', 2);
    }

    public function test_can_mark_single_notification_as_read_from_bell(): void
    {
        $notification = Notification::factory()->create(['user_id' => $this->user->id, 'read_at' => null]);

        Livewire::test(NotificationsBell::class)
            ->call('markAsRead', $notification->id)
            ->assertSet('unreadCount', 0);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_can_mark_all_notifications_as_read_from_bell(): void
    {
        Notification::factory()->count(3)->create(['user_id' => $this->user->id, 'read_at' => null]);

        Livewire::test(NotificationsBell::class)
            ->call('markAllAsRead')
            ->assertSet('unreadCount', 0);

        $this->assertSame(0, Notification::where('user_id', $this->user->id)->whereNull('read_at')->count());
    }

    public function test_notifications_resource_is_visible_in_sidebar_navigation(): void
    {
        $this->assertTrue(NotificationResource::shouldRegisterNavigation());
    }

    public function test_bell_is_rendered_on_profile_pages(): void
    {
        Notification::factory()->create(['user_id' => $this->user->id, 'read_at' => null]);

        $this->get('/profile/profile')
            ->assertOk()
            ->assertSeeLivewire(NotificationsBell::class);
    }
}
