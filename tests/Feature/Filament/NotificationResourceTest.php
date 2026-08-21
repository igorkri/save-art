<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Notifications\NotificationResource;
use App\Filament\Profile\Resources\Notifications\Pages\ListNotifications;
use App\Models\Notification;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class NotificationResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->artist()->profileCompleted()->create();

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_artist_sees_only_own_notifications(): void
    {
        $otherUser = User::factory()->artist()->profileCompleted()->create();

        $ownNotification = Notification::factory()->create(['user_id' => $this->user->id]);
        Notification::factory()->create(['user_id' => $otherUser->id]);

        Livewire::test(ListNotifications::class)
            ->assertCanSeeTableRecords([$ownNotification])
            ->assertCountTableRecords(1);
    }

    public function test_notifications_are_registered_in_profile_navigation(): void
    {
        $this->assertTrue(NotificationResource::shouldRegisterNavigation());
        $this->assertSame(
            '/profile/notifications',
            parse_url(NotificationResource::getUrl(panel: 'profile'), PHP_URL_PATH),
        );
    }

    public function test_artist_can_mark_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Livewire::test(ListNotifications::class)
            ->callTableAction('markAsRead', $notification);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_artist_can_mark_all_notifications_as_read(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Livewire::test(ListNotifications::class)
            ->callTableAction('markAllAsRead');

        $this->assertSame(0, Notification::where('user_id', $this->user->id)->whereNull('read_at')->count());
    }

    public function test_artist_can_delete_notification(): void
    {
        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(ListNotifications::class)
            ->callTableAction('delete', $notification);

        $this->assertDatabaseMissing('app_notifications', ['id' => $notification->id]);
    }

    public function test_view_project_action_is_visible_only_when_data_has_project_slug(): void
    {
        $withProject = Notification::factory()->create([
            'user_id' => $this->user->id,
            'data' => ['project_slug' => 'moye-polotno'],
        ]);
        $withoutProject = Notification::factory()->create([
            'user_id' => $this->user->id,
            'data' => [],
        ]);

        Livewire::test(ListNotifications::class)
            ->assertTableActionVisible('viewProject', $withProject)
            ->assertTableActionHidden('viewProject', $withoutProject);
    }

    public function test_view_project_action_links_to_frontend_project_page(): void
    {
        config(['app.frontend_url' => 'https://save-art.in.ua']);

        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'data' => ['project_slug' => 'moye-polotno'],
        ]);

        Livewire::test(ListNotifications::class)
            ->assertTableActionHasUrl('viewProject', 'https://save-art.in.ua/projects/moye-polotno', $notification);
    }

    public function test_notifications_page_is_registered_in_profile_panel(): void
    {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'title' => ['uk' => 'Тестове сповіщення', 'en' => 'Test notification'],
        ]);

        $this->get('/profile/notifications')
            ->assertOk()
            ->assertSee('Тестове сповіщення');
    }
}
