<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FinalResultUploadTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
        $this->project = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::InProgress,
        ]);
    }

    public function test_owner_can_upload_single_image_as_final_result(): void
    {
        $file = UploadedFile::fake()->image('final-artwork.jpg', 1920, 1080);
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/final-result/upload", [
                'type' => 'image',
                'files' => [$file],
                'description' => ['uk' => 'Фінальна робота', 'en' => 'Final artwork'],
            ]);
        $response->assertOk();
        $response->assertJsonPath('data.final_result.type', 'image');
        $response->assertJsonPath('data.final_result.description.uk', 'Фінальна робота');
        $this->assertNotNull($response->json('data.final_result.file.url'));
    }

    public function test_owner_can_upload_gallery_as_final_result(): void
    {
        $files = [
            UploadedFile::fake()->image('artwork1.jpg'),
            UploadedFile::fake()->image('artwork2.png'),
            UploadedFile::fake()->image('artwork3.jpg'),
        ];
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/final-result/upload", [
                'type' => 'gallery',
                'files' => $files,
            ]);
        $response->assertOk();
        $response->assertJsonPath('data.final_result.type', 'gallery');
        $response->assertJsonCount(3, 'data.final_result.files');
    }

    public function test_owner_can_upload_video_file_as_final_result(): void
    {
        $file = UploadedFile::fake()->create('presentation.mp4', 5000, 'video/mp4');
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/final-result/upload", [
                'type' => 'video',
                'files' => [$file],
                'description' => ['uk' => 'Відео презентація'],
            ]);
        $response->assertOk();
        $response->assertJsonPath('data.final_result.type', 'video');
        $this->assertNotNull($response->json('data.final_result.file.url'));
    }

    public function test_owner_can_upload_document_as_final_result(): void
    {
        $file = UploadedFile::fake()->create('catalog.pdf', 2000, 'application/pdf');
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/final-result/upload", [
                'type' => 'document',
                'files' => [$file],
            ]);
        $response->assertOk();
        $response->assertJsonPath('data.final_result.type', 'document');
        $this->assertNotNull($response->json('data.final_result.file.url'));
    }

    public function test_cannot_upload_final_result_for_draft_project(): void
    {
        $draftProject = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::Draft,
        ]);
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$draftProject->id}/final-result/upload", [
                'type' => 'image',
                'files' => [UploadedFile::fake()->image('test.jpg')],
            ]);
        $response->assertUnprocessable();
    }

    public function test_can_update_final_result_for_completed_project(): void
    {
        $completedProject = Project::factory()->for($this->user)->create([
            'status' => ProjectStatus::Completed,
        ]);
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$completedProject->id}/final-result/upload", [
                'type' => 'image',
                'files' => [UploadedFile::fake()->image('update.jpg')],
            ]);
        $response->assertOk();
    }

    public function test_other_user_cannot_upload_final_result(): void
    {
        $otherUser = User::factory()->create();
        $response = $this->withHeaders($this->authHeaders($otherUser))
            ->postJson("/api/v1/my/projects/{$this->project->id}/final-result/upload", [
                'type' => 'image',
                'files' => [UploadedFile::fake()->image('hack.jpg')],
            ]);
        $response->assertForbidden();
    }

    public function test_files_required(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/final-result/upload", [
                'type' => 'image',
            ]);
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['files']);
    }

    public function test_wrong_file_type_for_image(): void
    {
        $file = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/final-result/upload", [
                'type' => 'image',
                'files' => [$file],
            ]);
        $response->assertUnprocessable();
    }

    public function test_wrong_file_type_for_video(): void
    {
        $file = UploadedFile::fake()->image('image.jpg');
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/my/projects/{$this->project->id}/final-result/upload", [
                'type' => 'video',
                'files' => [$file],
            ]);
        $response->assertUnprocessable();
    }
}
