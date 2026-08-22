<?php

namespace Tests\Feature\Filament;

use App\Enums\ProjectSource;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProjectsTableSourceColumnTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'admin@example.com',
        ]);
    }

    public function test_table_renders_source_column_for_both_project_sources(): void
    {
        $saveArtProject = Project::factory()->create(['source' => ProjectSource::SaveArt]);
        $artUaInfoProject = Project::factory()->create(['source' => ProjectSource::ArtUaInfo]);

        $this->actingAs($this->admin);

        Livewire::test(ListProjects::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$saveArtProject, $artUaInfoProject])
            ->assertSee('Проєкт (SaveArt)')
            ->assertSee('Робота (Art-UA-Info)');
    }

    public function test_can_filter_table_by_source(): void
    {
        $saveArtProject = Project::factory()->create(['source' => ProjectSource::SaveArt]);
        $artUaInfoProject = Project::factory()->create(['source' => ProjectSource::ArtUaInfo]);

        $this->actingAs($this->admin);

        Livewire::test(ListProjects::class)
            ->filterTable('source', ProjectSource::ArtUaInfo->value)
            ->assertCanSeeTableRecords([$artUaInfoProject])
            ->assertCanNotSeeTableRecords([$saveArtProject]);
    }
}
