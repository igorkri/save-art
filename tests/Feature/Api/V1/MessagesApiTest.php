<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;

class MessagesApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ==========================================
    // Список повідомлень
    // ==========================================

    public function test_can_get_messages(): void
    {
        Message::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/messages');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'is_read',
                        'created_at',
                    ],
                ],
            ]);
    }

    // ==========================================
    // Створення повідомлення
    // ==========================================

    public function test_can_send_message(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/messages', [
                'subject' => 'Тема повідомлення',
                'content' => 'Текст повідомлення адміністрації',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('messages', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_message_requires_content(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/messages', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    // ==========================================
    // Перегляд повідомлення
    // ==========================================

    public function test_can_view_message(): void
    {
        $message = Message::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/messages/{$message->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $message->id);
    }

    public function test_cannot_view_other_user_message(): void
    {
        $message = Message::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/messages/{$message->id}");

        $response->assertForbidden();
    }

    // ==========================================
    // Кількість непрочитаних
    // ==========================================

    public function test_can_get_unread_count(): void
    {
        Message::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
            'is_from_admin' => true,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/messages/unread-count');

        $response->assertOk()
            ->assertJsonPath('data.count', 3);
    }

    // ==========================================
    // Позначити всі як прочитані
    // ==========================================

    public function test_can_mark_all_as_read(): void
    {
        Message::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/messages/mark-all-read');

        $response->assertOk();
    }

    // ==========================================
    // Написати автору проекту
    // ==========================================

    public function test_can_contact_project_author(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/projects/{$project->id}/contact-author", [
                'message' => 'Маю питання щодо вашого проекту',
            ]);

        $response->assertOk();
    }

    public function test_cannot_contact_own_project(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/projects/{$project->id}/contact-author", [
                'message' => 'Повідомлення',
            ]);

        $response->assertForbidden();
    }
}
