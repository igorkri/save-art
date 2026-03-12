<?php

namespace Tests\Feature\Api\V1;

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
            ->assertJsonCount(3, 'data');
    }

    // ==========================================
    // Відправка повідомлення
    // ==========================================

    public function test_can_send_message(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/messages', [
                'content' => 'Моє повідомлення для адміністрації платформи',
                'subject' => 'Питання',
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Повідомлення надіслано.');

        $this->assertDatabaseHas('messages', [
            'user_id' => $this->user->id,
            'direction' => 'user_to_admin',
        ]);
    }

    public function test_message_requires_content(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/messages', [
                'subject' => 'Тема без вмісту',
            ]);

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
        // Повідомлення від адміна (непрочитані)
        Message::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'direction' => 'admin_to_user',
            'read_at' => null,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/messages/unread-count');

        $response->assertOk()
            ->assertJsonPath('unread_count', 3);
    }

    // ==========================================
    // Позначити всі як прочитані
    // ==========================================

    public function test_can_mark_all_as_read(): void
    {
        Message::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'direction' => 'admin_to_user',
            'read_at' => null,
        ]);

        // Правильний роут з routes/api.php - /mark-all-read
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/messages/mark-all-read');

        $response->assertOk();

        $this->assertEquals(
            0,
            Message::where('user_id', $this->user->id)
                ->where('direction', 'admin_to_user')
                ->whereNull('read_at')
                ->count()
        );
    }

    // ==========================================
    // Зв'язок з автором проекту (через адміністрацію)
    // ==========================================

    public function test_can_contact_project_author(): void
    {
        $author = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $author->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/projects/{$project->id}/contact-author", [
                'content' => 'Маю питання щодо вашого проекту, будь ласка, допоможіть',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('messages', [
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'direction' => 'user_to_admin',
        ]);
    }

    public function test_can_contact_without_project_id_in_body(): void
    {
        $author = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $author->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/projects/{$project->id}/contact-author", [
                'content' => 'Повідомлення без project_id в body',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('messages', [
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'content' => 'Повідомлення без project_id в body',
        ]);
    }
}
