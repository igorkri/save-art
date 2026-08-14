<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_to_user_message_creates_notification(): void
    {
        $user = User::factory()->create();

        $message = Message::factory()
            ->for($user)
            ->fromAdmin()
            ->withSubject('Питання щодо проєкту')
            ->create();

        $notification = Notification::where('user_id', $user->id)
            ->where('type', NotificationType::Message)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('Питання щодо проєкту', $notification->title['uk']);
        $this->assertSame($message->content, $notification->message['uk']);
        $this->assertSame($message->id, $notification->data['message_id']);
    }

    public function test_system_to_user_message_creates_notification(): void
    {
        $user = User::factory()->create();

        Message::factory()->for($user)->fromSystem()->create();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $user->id,
            'type' => NotificationType::Message->value,
        ]);
    }

    public function test_user_to_admin_message_does_not_create_notification(): void
    {
        $user = User::factory()->create();

        Message::factory()->for($user)->fromUser()->create();

        $this->assertDatabaseMissing('app_notifications', [
            'user_id' => $user->id,
        ]);
    }
}
