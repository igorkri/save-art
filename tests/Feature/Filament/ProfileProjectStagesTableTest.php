<?php

namespace Tests\Feature\Filament;

use App\Enums\StageStatus;
use App\Filament\Profile\Resources\Projects\Pages\CreateProject;
use App\Filament\Profile\Resources\Projects\Pages\EditProject;
use App\Filament\Profile\Resources\Projects\ProjectResource;
use App\Jobs\GenerateStageDocumentPdfThumbnail;
use App\Jobs\OptimizeStageDocumentImage;
use App\Models\ArtCategory;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProfileProjectStagesTableTest extends TestCase
{
    use RefreshDatabase;

    protected User $artist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artist = User::factory()->artist()->profileCompleted()->create();
        $this->actingAs($this->artist);
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    /**
     * Кожен етап рендериться компактною карткою у декілька рядків
     * (статус/назва, опис), згорнутою за замовчуванням. Порядок
     * тепер пишеться автоматично через ->orderColumn('order') замість
     * ручного поля "№", а дати started_at/completed_at приховані й
     * проставляються автоматично при зміні статусу.
     */
    public function test_artist_can_create_project_with_stage(): void
    {
        $category = ArtCategory::factory()->create();

        Livewire::test(CreateProject::class)
            ->set('data.art_category_id', $category->id)
            ->set('data.title', 'Проєкт з етапами')
            ->set('data.currency', 'UAH')
            ->set('data.budget_goal', 700)
            ->set('data.stages', [
                [
                    'status' => StageStatus::InProgress->value,
                    'title' => 'Ескізи',
                    'description' => 'Пошук композиції',
                    'budget_planned' => 300,
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $project = Project::where('title', 'Проєкт з етапами')->firstOrFail();

        $this->assertDatabaseHas('project_stages', [
            'project_id' => $project->id,
            'title' => 'Ескізи',
            'status' => StageStatus::InProgress->value,
            'order' => 1,
        ]);
    }

    /**
     * Перехід статусу на "В процесі" має проставити started_at автоматично,
     * а на "Завершений" — completed_at (обидва поля приховані від артиста).
     */
    public function test_started_at_and_completed_at_are_set_automatically_from_status(): void
    {
        Storage::fake('public');

        $category = ArtCategory::factory()->create();

        Livewire::test(CreateProject::class)
            ->set('data.art_category_id', $category->id)
            ->set('data.title', 'Проєкт з авто-датами')
            ->set('data.currency', 'UAH')
            ->set('data.budget_goal', 700)
            ->set('data.stages', [
                [
                    'status' => StageStatus::Planned->value,
                    'title' => 'Ескізи',
                    'budget_planned' => 300,
                ],
            ])
            ->set('data.stages.0.status', StageStatus::InProgress->value)
            ->assertSet('data.stages.0.started_at', now()->toDateString())
            ->set('data.stages.0.status', StageStatus::Completed->value)
            ->assertSet('data.stages.0.completed_at', now()->toDateString())
            ->set('data.stages.0.budget_actual', 280)
            ->set('data.stages.0.documents', [UploadedFile::fake()->create('report.pdf', 10, 'application/pdf')])
            ->call('create')
            ->assertHasNoErrors();

        $project = Project::where('title', 'Проєкт з авто-датами')->firstOrFail();

        $this->assertDatabaseHas('project_stages', [
            'project_id' => $project->id,
            'title' => 'Ескізи',
            'status' => StageStatus::Completed->value,
            'started_at' => now()->startOfDay(),
            'completed_at' => now()->startOfDay(),
            'budget_actual' => 280,
        ]);

        $stage = $project->stages()->first();
        $this->assertCount(1, $stage->documents);
        $this->assertSame('document', $stage->documents[0]['type']);
    }

    /**
     * Регресія: documents зберігається у БД як [{type,file,file_url,uploaded_at}],
     * а FileUpload::multiple() під час завантаження форми очікує плоский масив
     * шляхів — без конвертації при гідратації відкриття форми редагування
     * падало з TypeError (BaseFileUpload::hydrateFiles(): array given).
     */
    public function test_editing_stage_with_saved_documents_does_not_crash(): void
    {
        $project = Project::factory()->create(['user_id' => $this->artist->id]);
        $project->stages()->create([
            'order' => 1,
            'status' => StageStatus::Completed,
            'title' => 'Друк накладу',
            'budget_planned' => 500,
            'budget_actual' => 480,
            'started_at' => now(),
            'completed_at' => now(),
            'documents' => [
                [
                    'type' => 'document',
                    'file' => 'projects/stage-documents/example.pdf',
                    'file_url' => 'https://save-art.ddev.site/storage/projects/stage-documents/example.pdf',
                    'uploaded_at' => now()->toISOString(),
                ],
            ],
        ]);

        // Головна перевірка — що монтування форми не кидає TypeError під час
        // гідратації FileUpload::multiple() зі збереженим збагаченим форматом.
        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->assertSuccessful();
    }

    /**
     * Нове фото-фото звіту після збереження ставиться в чергу на стиснення
     * (OptimizeStageDocumentImage), а вже існуючий (незмінений) документ —
     * ні, щоб не ганяти обробку одного й того самого файлу щоразу.
     */
    public function test_new_photo_document_is_queued_for_optimization_but_existing_one_is_not(): void
    {
        Queue::fake();
        Storage::fake('public');

        $project = Project::factory()->create(['user_id' => $this->artist->id]);
        $stage = $project->stages()->create([
            'order' => 1,
            'status' => StageStatus::Completed,
            'title' => 'Друк накладу',
            'budget_planned' => 500,
            'budget_actual' => 480,
            'started_at' => now(),
            'completed_at' => now(),
            'documents' => [
                [
                    'type' => 'photo',
                    'file' => 'projects/stage-documents/existing.png',
                    'file_url' => 'https://save-art.ddev.site/storage/projects/stage-documents/existing.png',
                    'uploaded_at' => now()->toISOString(),
                ],
            ],
        ]);

        Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
            ->set("data.stages.record-{$stage->id}.title", 'Друк накладу (оновлено)')
            ->set("data.stages.record-{$stage->id}.documents.1", UploadedFile::fake()->image('receipt.png'))
            ->call('save')
            ->assertHasNoErrors();

        Queue::assertPushed(OptimizeStageDocumentImage::class, 1);
        Queue::assertPushed(fn (OptimizeStageDocumentImage $job): bool => $job->path !== 'projects/stage-documents/existing.png');
    }

    /**
     * Наскрізна перевірка генерації мініатюри PDF через реальний Ghostscript
     * (без Imagick — на проді policy.xml забороняє PDF-кодер ImageMagick).
     * QUEUE_CONNECTION у тестах = sync, тож job виконується одразу.
     *
     * Мініатюра НЕ зберігається окремим полем у documents (це поле повністю
     * перезаписується формою при кожному save() і будь-яке дописане джобою
     * поле губилося б при наступному редагуванні) — її шлях детермінований:
     * {file}-thumb.png.
     */
    public function test_pdf_thumbnail_is_generated_via_ghostscript_after_save(): void
    {
        Storage::fake('public');

        // Мінімальний валідний PDF (порожня сторінка), достатній для gs.
        $minimalPdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000052 00000 n \n0000000101 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF";

        $category = ArtCategory::factory()->create();

        Livewire::test(CreateProject::class)
            ->set('data.art_category_id', $category->id)
            ->set('data.title', 'Проєкт з PDF-звітом')
            ->set('data.currency', 'UAH')
            ->set('data.budget_goal', 700)
            ->set('data.stages', [
                [
                    'status' => StageStatus::Completed->value,
                    'title' => 'Друк накладу',
                    'budget_planned' => 300,
                    'budget_actual' => 280,
                ],
            ])
            ->set('data.stages.0.documents', [
                UploadedFile::fake()->createWithContent('report.pdf', $minimalPdf),
            ])
            ->call('create')
            ->assertHasNoErrors();

        $project = Project::where('title', 'Проєкт з PDF-звітом')->firstOrFail();
        $stage = $project->stages()->firstOrFail();
        $documentPath = $stage->documents[0]['file'];

        Storage::disk('public')->assertExists(GenerateStageDocumentPdfThumbnail::thumbnailPathFor($documentPath));
    }

    /**
     * FilePond завжди показує загальну іконку документа для PDF (Filament не
     * має server-side хука для кастомного превью per-файл), тож мініатюру
     * підставляємо DOM-патчем на клієнті (ProfilePanelProvider::pdfThumbnailPreviewScript) —
     * скрипт живе на рівні розмітки сторінки панелі, а не Livewire-компонента,
     * тож перевіряємо його наявність через звичайний HTTP-запит сторінки.
     */
    public function test_pdf_thumbnail_preview_script_is_present_on_edit_page(): void
    {
        $project = Project::factory()->create(['user_id' => $this->artist->id]);
        $project->stages()->create([
            'order' => 1,
            'status' => StageStatus::Completed,
            'title' => 'Друк накладу',
            'budget_planned' => 500,
            'budget_actual' => 480,
        ]);

        $this->get(ProjectResource::getUrl('edit', ['record' => $project]))
            ->assertOk()
            ->assertSee('patchPdfPreviews', escape: false);
    }

    /**
     * За замовчуванням FileUpload прибирає файл лише зі стану форми — фізично
     * на диску він лишається "сиротою". deleteUploadedFileUsing() у
     * ProjectForm має видаляти і сам файл, і його -thumb.png (якщо є).
     */
    public function test_removing_document_deletes_it_from_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('projects/stage-documents/report.pdf', 'fake-pdf-content');
        Storage::disk('public')->put('projects/stage-documents/report-thumb.png', 'fake-png-content');

        $project = Project::factory()->create(['user_id' => $this->artist->id]);
        $stage = $project->stages()->create([
            'order' => 1,
            'status' => StageStatus::Completed,
            'title' => 'Друк накладу',
            'budget_planned' => 500,
            'budget_actual' => 480,
            'documents' => [
                [
                    'type' => 'document',
                    'file' => 'projects/stage-documents/report.pdf',
                    'file_url' => Storage::disk('public')->url('projects/stage-documents/report.pdf'),
                    'uploaded_at' => now()->toISOString(),
                ],
            ],
        ]);

        $componentKey = "form.stages.record-{$stage->id}.documents";

        $test = Livewire::test(EditProject::class, ['record' => $project->getRouteKey()]);

        // FileUpload перекладає стан у внутрішній UUID-ключ per файл
        // (hydrateFiles()) — це не той самий ключ, що ми ставили в
        // afterStateHydrated (плоский індекс), тож беремо його з реального стану.
        $fileKey = array_key_first($test->get("data.stages.record-{$stage->id}.documents"));

        $test->instance()->callSchemaComponentMethod($componentKey, 'deleteUploadedFile', ['fileKey' => $fileKey]);

        Storage::disk('public')->assertMissing('projects/stage-documents/report.pdf');
        Storage::disk('public')->assertMissing('projects/stage-documents/report-thumb.png');
    }
}
