<?php
namespace Tests\Unit;
use App\Models\ProfileDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ProfileDocumentModelTest extends TestCase
{
    use RefreshDatabase;
    public function test_can_create_profile_document(): void
    {
        $user = User::factory()->create();
        $document = ProfileDocument::create([
            'user_id' => $user->id,
            'file_path' => 'profile_documents/test.pdf',
            'hash' => hash('sha256', 'test content'),
            'sign_status' => 'pending',
            'service' => 'diia',
        ]);
        $this->assertDatabaseHas('profile_documents', [
            'user_id' => $user->id,
            'file_path' => 'profile_documents/test.pdf',
            'sign_status' => 'pending',
            'service' => 'diia',
        ]);
        $this->assertEquals($user->id, $document->user_id);
    }
    public function test_profile_document_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $document = ProfileDocument::create([
            'user_id' => $user->id,
            'file_path' => 'profile_documents/test.pdf',
            'hash' => hash('sha256', 'test content'),
            'sign_status' => 'pending',
            'service' => 'diia',
        ]);
        $this->assertInstanceOf(User::class, $document->user);
        $this->assertEquals($user->id, $document->user->id);
    }
    public function test_user_has_many_profile_documents(): void
    {
        $user = User::factory()->create();
        ProfileDocument::create([
            'user_id' => $user->id,
            'file_path' => 'profile_documents/test1.pdf',
            'hash' => hash('sha256', 'test content 1'),
            'sign_status' => 'pending',
            'service' => 'diia',
        ]);
        ProfileDocument::create([
            'user_id' => $user->id,
            'file_path' => 'profile_documents/test2.pdf',
            'hash' => hash('sha256', 'test content 2'),
            'sign_status' => 'pending',
            'service' => 'vchasno',
        ]);
        $this->assertCount(2, $user->profileDocuments);
    }
}
