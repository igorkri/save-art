<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Projects\Pages\CreateProject;
use App\Filament\Profile\Resources\Projects\Pages\EditProject;
use App\Models\ArtCategory;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProfileProjectContentBlocksTest extends TestCase
{
    use RefreshDatabase;

    protected User $artist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artist = User::factory()->artist()->create();
        $this->actingAs($this->artist);
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_artist_can_create_project_with_content_blocks(): void
    {
        $category = ArtCategory::factory()->create();

        Livewire::test(CreateProject::class)
            ->set('data.art_category_id', $category->id)
            ->set('data.title', 'Проєкт з контентом')
            ->set('data.currency', 'UAH')
            ->set('data.budget_goal', 1000)
            ->set('data.content_blocks', [
                [
                    'type' => 'heading',
                    'data' => ['heading_text' => 'Про проєкт'],
                ],
                [
                    'type' => 'paragraph',
                    'data' => ['paragraph_text' => 'Детальний опис проєкту.'],
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $project = Project::where('title', 'Проєкт з контентом')->firstOrFail();

        $this->assertCount(2, $project->content_blocks);
        $this->assertSame('heading', $project->content_blocks[0]['type']);
        $this->assertSame('Про проєкт', $project->content_blocks[0]['heading_text']);
        $this->assertSame('paragraph', $project->content_blocks[1]['type']);
        $this->assertSame('Детальний опис проєкту.', $project->content_blocks[1]['paragraph_text']);
    }

    public function test_artist_can_edit_project_content_blocks(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->artist->id,
            'content_blocks' => [
                ['type' => 'heading', 'heading_text' => 'Стара назва'],
            ],
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->set('data.content_blocks', [
                ['type' => 'heading', 'data' => ['heading_text' => 'Нова назва']],
                ['type' => 'paragraph', 'data' => ['paragraph_text' => 'Новий параграф']],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $project->refresh();

        $this->assertCount(2, $project->content_blocks);
        $this->assertSame('heading', $project->content_blocks[0]['type']);
        $this->assertSame('Нова назва', $project->content_blocks[0]['heading_text']);
        $this->assertSame('paragraph', $project->content_blocks[1]['type']);
        $this->assertSame('Новий параграф', $project->content_blocks[1]['paragraph_text']);
    }
}
