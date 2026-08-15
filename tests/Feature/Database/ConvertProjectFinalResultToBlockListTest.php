<?php

namespace Tests\Feature\Database;

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Тестує міграцію 2026_08_16_000001_convert_project_final_result_to_block_list, яка
 * конвертує старий формат final_result (єдиний об'єкт з uploadFinalResult) у новий
 * список блоків, що розуміють Filament-панель і публічні фронтенди.
 *
 * RefreshDatabase вже прогнав усі міграції (включно з цією) на порожній БД до старту
 * тесту — тому тут інстанціюємо файл міграції напряму й викликаємо up() ще раз проти
 * рядків зі старим форматом, вставлених уже після міграції (імітує реальні "старі" дані).
 */
class ConvertProjectFinalResultToBlockListTest extends TestCase
{
    use RefreshDatabase;

    private Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migration = require database_path('migrations/2026_08_16_000001_convert_project_final_result_to_block_list.php');
    }

    private function setRawFinalResult(Project $project, array $value): void
    {
        DB::table('projects')->where('id', $project->id)->update([
            'final_result' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function test_converts_single_image_to_gallery_block(): void
    {
        $project = Project::factory()->create();
        $this->setRawFinalResult($project, [
            'type' => 'image',
            'file' => ['path' => 'projects/1/final-result/photo.jpg', 'url' => '/storage/projects/1/final-result/photo.jpg'],
            'uploaded_at' => now()->toISOString(),
        ]);

        $this->migration->up();

        $project->refresh();

        $this->assertSame([
            ['type' => 'gallery', 'images' => ['projects/1/final-result/photo.jpg']],
        ], $project->final_result);
    }

    public function test_converts_gallery_files_to_gallery_block(): void
    {
        $project = Project::factory()->create();
        $this->setRawFinalResult($project, [
            'type' => 'gallery',
            'files' => [
                ['path' => 'projects/1/final-result/a.jpg'],
                ['path' => 'projects/1/final-result/b.jpg'],
            ],
        ]);

        $this->migration->up();

        $project->refresh();

        $this->assertSame([
            ['type' => 'gallery', 'images' => ['projects/1/final-result/a.jpg', 'projects/1/final-result/b.jpg']],
        ], $project->final_result);
    }

    public function test_converts_link_to_platform_specific_block(): void
    {
        $project = Project::factory()->create();
        $this->setRawFinalResult($project, [
            'type' => 'link',
            'url' => 'https://www.youtube.com/watch?v=abc123',
        ]);

        $this->migration->up();

        $project->refresh();

        $this->assertSame([
            ['type' => 'youtube', 'url' => 'https://www.youtube.com/watch?v=abc123'],
        ], $project->final_result);
    }

    public function test_generic_link_without_known_platform_stays_link_type(): void
    {
        $project = Project::factory()->create();
        $this->setRawFinalResult($project, [
            'type' => 'link',
            'url' => 'https://example.com/my-work',
        ]);

        $this->migration->up();

        $project->refresh();

        $this->assertSame([
            ['type' => 'link', 'url' => 'https://example.com/my-work'],
        ], $project->final_result);
    }

    /**
     * video/document — завантажені файли без відповідника серед нових типів
     * (gallery/youtube/vimeo/issuu) — міграція їх свідомо не чіпає.
     */
    public function test_leaves_video_and_document_types_untouched(): void
    {
        $project = Project::factory()->create();
        $original = [
            'type' => 'video',
            'file' => ['path' => 'projects/1/final-result/movie.mp4'],
        ];
        $this->setRawFinalResult($project, $original);

        $this->migration->up();

        $project->refresh();

        $this->assertSame($original, $project->final_result);
    }

    public function test_already_new_format_is_left_untouched(): void
    {
        $project = Project::factory()->create();
        $new = [
            ['type' => 'youtube', 'url' => 'https://www.youtube.com/watch?v=existing'],
            ['type' => 'gallery', 'images' => ['projects/1/final-result/x.jpg']],
        ];
        $this->setRawFinalResult($project, $new);

        $this->migration->up();

        $project->refresh();

        $this->assertSame($new, $project->final_result);
    }

    public function test_null_final_result_is_ignored(): void
    {
        $project = Project::factory()->create(['final_result' => null]);

        $this->migration->up();

        $project->refresh();

        $this->assertNull($project->final_result);
    }
}
