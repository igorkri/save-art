<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Pages\Documents;
use App\Models\ProfileDocument;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProfileDocumentsPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->artist()->profileCompleted()->create();

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_artist_can_add_and_remove_own_profile_documents(): void
    {
        Storage::disk('public')->put('profile_documents/old.pdf', 'old document');
        Storage::disk('public')->put('profile_documents/new.pdf', 'new document');

        $oldDocument = $this->user->profileDocuments()->create([
            'file_path' => 'profile_documents/old.pdf',
            'hash' => hash('sha256', 'old document'),
            'sign_status' => 'pending',
            'service' => 'diia',
        ]);

        Livewire::test(Documents::class)
            ->set('data.profileDocuments', ['profile_documents/new.pdf'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('profile_documents', ['id' => $oldDocument->id]);
        $this->assertDatabaseHas('profile_documents', [
            'user_id' => $this->user->id,
            'file_path' => 'profile_documents/new.pdf',
            'hash' => hash('sha256', 'new document'),
        ]);
        $this->assertSame(1, ProfileDocument::query()->where('user_id', $this->user->id)->count());
    }

    public function test_documents_page_remains_accessible_without_a_navigation_item(): void
    {
        $this->assertFalse(Documents::shouldRegisterNavigation());

        $this->get('/profile/documents')
            ->assertOk()
            ->assertSee('Документи');
    }
}
