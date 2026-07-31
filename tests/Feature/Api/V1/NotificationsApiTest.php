<?php

namespace Tests\Feature\Api\V1;

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
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_only_unread_notifications(): void
    {
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => now(),
        ]);

        // Правильний параметр - unread_only
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/notifications?unread_only=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_notifications_are_paginated(): void
    {
        Notification::factory()->count(25)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/notifications');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page'],
            ]);
    }

    // ==========================================
    // Перегляд сповіщення (формат: {source}/{id})
    // ==========================================

    public function test_can_get_notification(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Формат роута: /my/notifications/{source}/{id}
        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/notifications/notification/{$notification->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $notification->id);
    }

    public function test_cannot_get_other_user_notification(): void
    {
        $notification = Notification::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/notifications/notification/{$notification->id}");

        // 404 тому що сповіщення не належить поточному користувачу
        $response->assertNotFound();
    }

    // ==========================================
    // Позначити як прочитане
    // ==========================================

    public function test_can_mark_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        // Формат роута: /my/notifications/{source}/{id}/read
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/notifications/notification/{$notification->id}/read");

        $response->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_can_mark_all_notifications_as_read(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/my/notifications/read-all');

        $response->assertOk();

        $this->assertEquals(
            0,
            Notification::where('user_id', $this->user->id)
                ->whereNull('read_at')
                ->count()
        );
    }

    // ==========================================
    // Видалення сповіщень
    // ==========================================

    public function test_can_delete_notification(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Формат роута: /my/notifications/{source}/{id}
        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/notifications/notification/{$notification->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('app_notifications', [
            'id' => $notification->id,
        ]);
    }

    // ==========================================
    // Кількість непрочитаних
    // ==========================================

    public function test_can_get_unread_count(): void
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'read_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/notifications/unread-count');

        // Формат відповіді: unread_count, unread_notifications, unread_messages
        $response->assertOk()
            ->assertJsonPath('unread_notifications', 5);
    }

    // ==========================================
    // Захист
    // ==========================================

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/my/notifications');

        $response->assertUnauthorized();
    }

    // ==========================================
    // Неймспейс art-ua-info (той самий NotificationController)
    // ==========================================

    public function test_can_get_notifications_via_art_ua_info_namespace(): void
    {
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/art-ua-info/my/notifications');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
