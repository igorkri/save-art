<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Catalogs\Pages\CreateCatalog;
use App\Filament\Profile\Resources\Catalogs\Pages\ListCatalogs;
use App\Models\ArtCatalog;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class CatalogResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->artist()->create();

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_artist_sees_only_own_catalogs(): void
    {
        $otherUser = User::factory()->artist()->create();

        $ownCatalog = ArtCatalog::factory()->for($this->user)->create();
        ArtCatalog::factory()->for($otherUser)->create();

        Livewire::test(ListCatalogs::class)
            ->assertCanSeeTableRecords([$ownCatalog])
            ->assertCountTableRecords(1);
    }

    public function test_artist_can_create_catalog_with_user_id_auto_set(): void
    {
        Livewire::test(CreateCatalog::class)
            ->fillForm([
                'title.uk' => 'Мій каталог',
                'title.en' => 'My catalog',
                'image' => UploadedFile::fake()->image('cover.jpg'),
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('art_catalogs', [
            'user_id' => $this->user->id,
        ]);

        $catalog = ArtCatalog::where('user_id', $this->user->id)->first();
        $this->assertSame('Мій каталог', $catalog->title['uk']);
    }

    public function test_setting_catalog_as_primary_unsets_previous_primary(): void
    {
        $oldPrimary = ArtCatalog::factory()->for($this->user)->create(['is_primary' => true]);
        $newPrimary = ArtCatalog::factory()->for($this->user)->create(['is_primary' => false]);

        Livewire::test(ListCatalogs::class)
            ->callTableAction('setPrimary', $newPrimary);

        $this->assertTrue($newPrimary->fresh()->is_primary);
        $this->assertFalse($oldPrimary->fresh()->is_primary);
    }
}
