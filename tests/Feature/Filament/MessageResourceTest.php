<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Messages\Pages\CreateMessage;
use App\Filament\Profile\Resources\Messages\Pages\ListMessages;
use App\Models\Message;
use App\Models\Notification;
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

    public function test_artist_sees_only_own_messages(): void
    {
        $otherUser = User::factory()->artist()->create();

        $ownMessage = Message::factory()->for($this->user)->create();
        Message::factory()->for($otherUser)->create();

        Livewire::test(ListMessages::class)
            ->assertCanSeeTableRecords([$ownMessage])
            ->assertCountTableRecords(1);
    }

    public function test_artist_can_reply_to_admin_message(): void
    {
        $message = Message::factory()
            ->for($this->user)
            ->fromAdmin()
            ->withSubject('Питання щодо проєкту')
            ->create();

        Livewire::test(ListMessages::class)
            ->callTableAction('reply', $message, data: [
                'content' => 'Дякую, все зрозуміло',
            ]);

        $this->assertDatabaseHas('messages', [
            'user_id' => $this->user->id,
            'direction' => Message::DIRECTION_USER_TO_ADMIN,
            'content' => 'Дякую, все зрозуміло',
            'subject' => 'Re: Питання щодо проєкту',
        ]);

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_reply_does_not_create_an_extra_notification_for_the_replying_user(): void
    {
        $message = Message::factory()->for($this->user)->fromAdmin()->create();

        // Сповіщення про вхідне повідомлення від адміна вже створене спостерігачем.
        $countBeforeReply = Notification::where('user_id', $this->user->id)->count();

        Livewire::test(ListMessages::class)
            ->callTableAction('reply', $message, data: [
                'content' => 'Відповідь',
            ]);

        // Власна відповідь користувача (user_to_admin) не повинна створювати йому ж ще одне сповіщення.
        $this->assertSame($countBeforeReply, Notification::where('user_id', $this->user->id)->count());
    }

    public function test_artist_can_mark_admin_message_as_read_without_replying(): void
    {
        $message = Message::factory()->for($this->user)->fromAdmin()->unread()->create();

        Livewire::test(ListMessages::class)
            ->callTableAction('markAsRead', $message);

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_artist_can_compose_new_message_to_admin(): void
    {
        Livewire::test(CreateMessage::class)
            ->fillForm([
                'subject' => 'Питання',
                'content' => 'Маю питання щодо виплат',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'user_id' => $this->user->id,
            'direction' => Message::DIRECTION_USER_TO_ADMIN,
            'subject' => 'Питання',
            'content' => 'Маю питання щодо виплат',
        ]);
    }

    public function test_reply_action_is_not_visible_for_own_messages(): void
    {
        $message = Message::factory()->for($this->user)->fromUser()->create();

        Livewire::test(ListMessages::class)
            ->assertTableActionHidden('reply', $message);
    }
}
