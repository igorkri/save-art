<?php

namespace Tests\Feature\Api\V1;

use App\Enums\StageStatus;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProjectStageDocumentsTest extends ApiTestCase
{
    private ProjectStage $stage;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->project = Project::factory()->for($this->user)->create();
        $this->stage = ProjectStage::factory()->for($this->project)->create([
            'status' => StageStatus::InProgress,
        ]);
    }

    public function test_user_can_upload_documents_to_stage(): void
    {
        $files = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.png'),
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/stages/{$this->stage->id}/documents", [
                'documents' => $files,
                'descriptions' => [
                    ['uk' => 'Чек за матеріали', 'en' => 'Receipt for materials'],
                    ['uk' => 'Фото прогресу', 'en' => 'Progress photo'],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonCount(2, 'data.documents');
        $response->assertJsonPath('data.documents.0.type', 'photo');
        $response->assertJsonPath('data.documents.0.description.uk', 'Чек за матеріали');
    }

    public function test_user_can_upload_pdf_documents(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/stages/{$this->stage->id}/documents", [
                'documents' => [$file],
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.documents.0.type', 'document');
    }

    public function test_user_cannot_upload_to_others_project_stage(): void
    {
        $otherUser = User::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->withHeaders($this->authHeaders($otherUser))
            ->postJson("/api/v1/my/projects/{$this->project->id}/stages/{$this->stage->id}/documents", [
                'documents' => [$file],
            ]);

        $response->assertForbidden();
    }

    public function test_user_can_delete_document_from_stage(): void
    {
        // Спочатку завантажимо документ
        $this->stage->update([
            'documents' => [
                [
                    'type' => 'photo',
                    'file' => 'test/photo.jpg',
                    'file_url' => 'http://test.com/storage/test/photo.jpg',
                    'original_name' => 'photo.jpg',
                    'description' => ['uk' => 'Тест', 'en' => 'Test'],
                    'uploaded_at' => now()->toISOString(),
                ],
            ],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/my/projects/{$this->project->id}/stages/{$this->stage->id}/documents/0");

        $response->assertOk();
        $response->assertJsonCount(0, 'data.documents');
    }

    public function test_validation_fails_for_invalid_file_types(): void
    {
        $file = UploadedFile::fake()->create('document.exe', 1024, 'application/octet-stream');

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/stages/{$this->stage->id}/documents", [
                'documents' => [$file],
            ]);

        $response->assertUnprocessable();
    }

    public function test_validation_fails_for_too_large_files(): void
    {
        $file = UploadedFile::fake()->create('large.jpg', 6000, 'image/jpeg');

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/stages/{$this->stage->id}/documents", [
                'documents' => [$file],
            ]);

        $response->assertUnprocessable();
    }

    public function test_stages_list_includes_documents(): void
    {
        $this->stage->update([
            'documents' => [
                [
                    'type' => 'photo',
                    'file' => 'test/photo.jpg',
                    'file_url' => 'http://test.com/storage/test/photo.jpg',
                    'original_name' => 'photo.jpg',
                    'description' => null,
                    'uploaded_at' => now()->toISOString(),
                ],
            ],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/projects/{$this->project->id}/stages");

        $response->assertOk();
        $response->assertJsonCount(1, 'data.0.documents');
        $response->assertJsonPath('data.0.documents.0.type', 'photo');
    }
}
