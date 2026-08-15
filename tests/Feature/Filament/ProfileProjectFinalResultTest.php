<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Projects\Pages\EditProject;
use App\Filament\Profile\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProfileProjectFinalResultTest extends TestCase
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

    /**
     * Крок "Фінальний результат" — тільки для проєктів у роботі й вище
     * (docs/project-lifecycle-flow.md). На чернетці показувати нічого, бо
     * там ще нема результату.
     */
    public function test_final_result_step_is_hidden_for_draft_project(): void
    {
        $project = Project::factory()->create(['user_id' => $this->artist->id]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertDontSee(__('profile_projects.tabs.final_result'));
    }

    public function test_final_result_step_is_visible_for_in_progress_project(): void
    {
        $project = Project::factory()->inProgress()->create(['user_id' => $this->artist->id]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertSee(__('profile_projects.tabs.final_result'));
    }

    public function test_artist_can_save_youtube_and_gallery_blocks_on_in_progress_project(): void
    {
        $project = Project::factory()->inProgress()->create(['user_id' => $this->artist->id]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->set('data.final_result', [
                ['type' => 'youtube', 'data' => ['url' => 'https://www.youtube.com/watch?v=abc123']],
                ['type' => 'gallery', 'data' => ['images' => [
                    'projects/final-result/result-1.jpg',
                    'projects/final-result/result-2.jpg',
                ]]],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $project->refresh();

        $this->assertCount(2, $project->final_result);
        $this->assertSame('youtube', $project->final_result[0]['type']);
        $this->assertSame('https://www.youtube.com/watch?v=abc123', $project->final_result[0]['url']);
        $this->assertSame('gallery', $project->final_result[1]['type']);
        $this->assertCount(2, $project->final_result[1]['images']);
    }

    /**
     * Посилання на YouTube-блок мусить справді вести на youtube.com/youtu.be —
     * довільне посилання (наприклад, vimeo) у цей блок не пройде.
     */
    public function test_youtube_block_rejects_non_youtube_url(): void
    {
        $project = Project::factory()->inProgress()->create(['user_id' => $this->artist->id]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->set('data.final_result', [
                ['type' => 'youtube', 'data' => ['url' => 'https://vimeo.com/12345']],
            ])
            ->call('save')
            ->assertHasErrors(['data.final_result.0.data.url']);
    }

    /**
     * Регресія: коли крок прихований (чернетка), у стані форми немає ключа
     * final_result — mutateFormDataBeforeSave не повинен через це затирати
     * вже наявні дані (актуально, якщо статус десь інде відкотили назад).
     */
    public function test_saving_draft_project_does_not_touch_final_result(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->artist->id,
            'final_result' => [['type' => 'youtube', 'url' => 'https://www.youtube.com/watch?v=existing']],
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->set('data.title', 'Оновлена назва')
            ->call('save')
            ->assertHasNoErrors();

        $project->refresh();

        $this->assertSame('Оновлена назва', $project->title);
        $this->assertCount(1, $project->final_result);
        $this->assertSame('https://www.youtube.com/watch?v=existing', $project->final_result[0]['url']);
    }

    /**
     * Регресія: завершений проєкт раніше не можна було відкрити на редагування
     * взагалі (ProjectPolicy::update() і кнопка "Редагувати" в списку не
     * враховували Completed/Sold) — саме тому "Фінальний результат" був
     * недоступний навіть тоді, коли крок мав бути видимим.
     */
    public function test_edit_page_is_accessible_for_completed_project(): void
    {
        $project = Project::factory()->completed()->create(['user_id' => $this->artist->id]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertSuccessful()
            ->assertSee(__('profile_projects.tabs.final_result'));
    }

    public function test_edit_action_is_visible_for_completed_project_in_table(): void
    {
        $project = Project::factory()->completed()->create(['user_id' => $this->artist->id]);

        Livewire::test(ListProjects::class)
            ->assertTableActionVisible('edit', $project);
    }

    public function test_artist_can_update_final_result_on_completed_project(): void
    {
        $project = Project::factory()->completed()->create([
            'user_id' => $this->artist->id,
            'final_result' => [['type' => 'vimeo', 'url' => 'https://vimeo.com/old']],
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->set('data.final_result', [
                ['type' => 'vimeo', 'data' => ['url' => 'https://vimeo.com/new']],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $project->refresh();

        $this->assertSame('https://vimeo.com/new', $project->final_result[0]['url']);
    }
}
