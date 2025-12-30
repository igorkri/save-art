<?php

namespace Tests\Feature;

use App\Models\ProfileDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileDocumentsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
    }

    public function test_can_get_all_documents(): void
    {
        // Создаём несколько документов
        $file = UploadedFile::fake()->create('document.pdf', 100);
        $path = $file->store('profile_documents', 'public');

        ProfileDocument::create([
            'user_id' => $this->user->id,
            'file_path' => $path,
            'hash' => hash('sha256', Storage::disk('public')->get($path)),
            'sign_status' => 'pending',
            'service' => 'diia',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/profile/documents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'documents' => [
                    '*' => [
                        'id',
                        'file_path',
                        'file_url',
                        'hash',
                        'sign_status',
                        'service',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_can_upload_document(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/profile/documents', [
                'file' => $file,
                'service' => 'diia',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'document' => [
                    'id',
                    'file_path',
                    'file_url',
                    'hash',
                    'sign_status',
                    'service',
                ],
            ]);

        $this->assertDatabaseHas('profile_documents', [
            'user_id' => $this->user->id,
            'sign_status' => 'pending',
            'service' => 'diia',
        ]);
    }

    public function test_cannot_upload_duplicate_document(): void
    {
        // Создаём файл с определённым содержимым
        $content = str_repeat('test content for pdf', 100);
        $file = UploadedFile::fake()->createWithContent('document.pdf', $content);
        $path = $file->store('profile_documents', 'public');
        $hash = hash('sha256', $content);

        // Создаём документ с таким же хешем
        ProfileDocument::create([
            'user_id' => $this->user->id,
            'file_path' => $path,
            'hash' => $hash,
            'sign_status' => 'pending',
            'service' => 'diia',
        ]);

        // Пытаемся загрузить файл с таким же содержимым
        $duplicateFile = UploadedFile::fake()->createWithContent('document_copy.pdf', $content);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/profile/documents', [
                'file' => $duplicateFile,
            ]);

        $response->assertStatus(409)
            ->assertJsonFragment([
                'message' => 'Документ з таким вмістом вже існує.',
            ]);
    }

    public function test_can_get_single_document(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);
        $path = $file->store('profile_documents', 'public');

        $document = ProfileDocument::create([
            'user_id' => $this->user->id,
            'file_path' => $path,
            'hash' => hash('sha256', Storage::disk('public')->get($path)),
            'sign_status' => 'pending',
            'service' => 'diia',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/profile/documents/{$document->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'document' => [
                    'id',
                    'file_path',
                    'file_url',
                    'hash',
                ],
            ]);
    }

    public function test_can_delete_document(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);
        $path = $file->store('profile_documents', 'public');

        $document = ProfileDocument::create([
            'user_id' => $this->user->id,
            'file_path' => $path,
            'hash' => hash('sha256', Storage::disk('public')->get($path)),
            'sign_status' => 'pending',
            'service' => 'diia',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/profile/documents/{$document->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Документ успішно видалено.',
            ]);

        $this->assertDatabaseMissing('profile_documents', [
            'id' => $document->id,
        ]);

        Storage::disk('public')->assertMissing($path);
    }

    public function test_cannot_access_other_user_document(): void
    {
        $otherUser = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);
        $path = $file->store('profile_documents', 'public');

        $document = ProfileDocument::create([
            'user_id' => $otherUser->id,
            'file_path' => $path,
            'hash' => hash('sha256', Storage::disk('public')->get($path)),
            'sign_status' => 'pending',
            'service' => 'diia',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/profile/documents/{$document->id}");

        $response->assertStatus(404);
    }

    public function test_upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/profile/documents', [
            'file' => $file,
        ]);

        $response->assertStatus(401);
    }

    public function test_upload_validates_file_type(): void
    {
        $file = UploadedFile::fake()->create('document.exe', 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/profile/documents', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_get_profile_includes_documents(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);
        $path = $file->store('profile_documents', 'public');

        ProfileDocument::create([
            'user_id' => $this->user->id,
            'file_path' => $path,
            'hash' => hash('sha256', Storage::disk('public')->get($path)),
            'sign_status' => 'pending',
            'service' => 'diia',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'profileDocuments' => [
                    '*' => [
                        'id',
                        'file_path',
                        'file_url',
                    ],
                ],
            ]);
    }
}
