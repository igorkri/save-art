<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Works\Pages\ListWorks;
use App\Filament\Profile\Resources\Works\WorkResource;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class WorkResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->artist()->profileCompleted()->create();

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_works_resource_shows_the_same_projects_for_the_current_artist(): void
    {
        $ownProject = Project::factory()->create(['user_id' => $this->user->id]);
        $otherProject = Project::factory()->create();

        Livewire::test(ListWorks::class)
            ->assertCanSeeTableRecords([$ownProject])
            ->assertCanNotSeeTableRecords([$otherProject]);
    }

    public function test_works_resource_has_independent_routes_without_duplicate_global_search(): void
    {
        $this->assertSame('Роботи', WorkResource::getNavigationLabel());
        $this->assertSame('/profile/works', parse_url(WorkResource::getUrl(panel: 'profile'), PHP_URL_PATH));
        $this->assertFalse(WorkResource::canGloballySearch());
    }

    public function test_works_create_page_renders_the_shared_project_form(): void
    {
        $this->get(WorkResource::getUrl('create', panel: 'profile'))
            ->assertOk()
            ->assertSee('Власник')
            ->assertDontSee('>Автор<', escape: false);
    }
}
