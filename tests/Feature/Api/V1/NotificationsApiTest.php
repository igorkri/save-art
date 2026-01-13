<?php

namespace Tests\Feature\Api\V1;

use App\Models\Message;
use App\Models\Notification;
use App\Models\User;

class NotificationsApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ==========================================
    // Список сповіщень
    // ==========================================

    public function test_can_get_notifications(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/notifications');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'title',
                        'is_read',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_can_filter_unread_notifications(): void
    {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/notifications?unread=1');

        $response->assertOk();
    }

    // ==========================================
    // Кількість непрочитаних
    // ==========================================

    public function test_can_get_unread_count(): void
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/notifications/unread-count');

        $response->assertOk()
            ->assertJsonPath('data.count', 5);
    }

    // ==========================================
    // Позначити як прочитане
    // ==========================================

    public function test_can_mark_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/notifications/notification/{$notification->id}/read");

        $response->assertOk();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }

    public function test_can_mark_message_as_read(): void
    {
        $message = Message::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/notifications/message/{$message->id}/read");

        $response->assertOk();

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'is_read' => true,
        ]);
    }

    public function test_can_mark_all_as_read(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/notifications/read-all');

        $response->assertOk();

        $this->assertEquals(
            0,
            Notification::where('user_id', $this->user->id)->where('is_read', false)->count()
        );
    }

    // ==========================================
    // Перегляд сповіщення
    // ==========================================

    public function test_can_view_notification(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/notifications/notification/{$notification->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $notification->id);
    }

    public function test_cannot_view_other_user_notification(): void
    {
        $notification = Notification::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/notifications/notification/{$notification->id}");

        $response->assertForbidden();
    }

    // ==========================================
    // Видалення сповіщення
    // ==========================================

    public function test_can_delete_notification(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/notifications/notification/{$notification->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }
}
