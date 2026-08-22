<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Messages\Pages\ListMessages;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class MessageResourceTest extends TestCase
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

    public function test_artist_sees_only_own_messages_in_general_thread(): void
    {
        $otherUser = User::factory()->artist()->create();

        $ownMessage = Message::factory()->for($this->user)->create();
        Message::factory()->for($otherUser)->create();

        Livewire::test(ListMessages::class)
            ->assertSee($ownMessage->content);
    }

    public function test_artist_can_send_a_message_in_the_active_thread(): void
    {
        Livewire::test(ListMessages::class)
            ->set('messageInput', 'Маю питання щодо виплат')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'user_id' => $this->user->id,
            'project_id' => null,
            'direction' => Message::DIRECTION_USER_TO_ADMIN,
            'content' => 'Маю питання щодо виплат',
        ]);
    }

    public function test_sending_an_empty_message_is_rejected(): void
    {
        Livewire::test(ListMessages::class)
            ->set('messageInput', '')
            ->call('sendMessage')
            ->assertHasErrors(['messageInput' => 'required']);

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_opening_a_thread_marks_admin_messages_as_read(): void
    {
        $message = Message::factory()->for($this->user)->fromAdmin()->unread()->create();

        Livewire::test(ListMessages::class);

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_reply_does_not_create_an_extra_notification_for_the_replying_user(): void
    {
        Message::factory()->for($this->user)->fromAdmin()->create();

        // Сповіщення про вхідне повідомлення від адміна вже створене спостерігачем.
        $countBeforeReply = Notification::where('user_id', $this->user->id)->count();

        Livewire::test(ListMessages::class)
            ->set('messageInput', 'Відповідь')
            ->call('sendMessage');

        // Власна відповідь користувача (user_to_admin) не повинна створювати йому ж ще одне сповіщення.
        $this->assertSame($countBeforeReply, Notification::where('user_id', $this->user->id)->count());
    }

    public function test_artist_can_switch_to_a_project_thread_and_send_a_message_there(): void
    {
        $project = Project::factory()->for($this->user)->create();

        Livewire::test(ListMessages::class)
            ->call('selectThread', $project->id)
            ->assertSet('activeProjectId', $project->id)
            ->set('messageInput', 'Питання по проєкту')
            ->call('sendMessage');

        $this->assertDatabaseHas('messages', [
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'direction' => Message::DIRECTION_USER_TO_ADMIN,
            'content' => 'Питання по проєкту',
        ]);
    }

    public function test_general_thread_messages_are_not_mixed_with_project_thread(): void
    {
        $project = Project::factory()->for($this->user)->create();

        $generalMessage = Message::factory()->for($this->user)->create(['project_id' => null]);
        $projectMessage = Message::factory()->for($this->user)->create(['project_id' => $project->id]);

        $component = Livewire::test(ListMessages::class)
            ->assertSee($generalMessage->content);

        $component->call('selectThread', $project->id)
            ->assertSee($projectMessage->content);
    }
}
