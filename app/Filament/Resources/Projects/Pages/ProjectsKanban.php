<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Services\ProjectWorkflowService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Collection;

class ProjectsKanban extends Page
{
    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.projects.pages.projects-kanban';

    public string $search = '';

    public function getTitle(): string
    {
        return 'Канбан проєктів';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list')
                ->label('Список')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn (): string => ProjectResource::getUrl('index')),
        ];
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->label('Переглянути')
            ->icon('heroicon-o-eye')
            ->modalWidth(Width::Screen)
            ->modalHeading(fn (array $arguments): string => $this->findProject($arguments)->title['uk']
                ?? $this->findProject($arguments)->title['en']
                ?? 'Проєкт')
            ->modalContent(fn (array $arguments) => view(
                'filament.resources.projects.pages.partials.project-view',
                [
                    'project' => $this->findProject($arguments),
                    'statusOptions' => $this->getStatusOptions($this->findProject($arguments)),
                ]
            ))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Закрити');
    }

    private function findProject(array $arguments): Project
    {
        return Project::query()
            ->with(['user', 'artCategory', 'stages', 'bonuses', 'donations.user', 'donations.bonus'])
            ->findOrFail($arguments['project']);
    }

    /**
     * @return array<string, string>
     */
    private function getStatusOptions(Project $project): array
    {
        $options = [$project->status->value => $project->status->getLabel()];

        foreach (app(ProjectWorkflowService::class)->getAllowedTransitions($project) as $status) {
            $options[$status->value] = $status->getLabel();
        }

        return $options;
    }

    /**
     * @return array<string, Collection<int, Project>>
     */
    public function getColumns(): array
    {
        $query = Project::query()->with(['user', 'artCategory']);

        if (filled($this->search)) {
            $search = '%'.mb_strtolower($this->search).'%';

            $query->where(function ($query) use ($search) {
                $query->whereRaw("lower(json_extract(title, '$.\"uk\"')) like ?", [$search])
                    ->orWhereRaw("lower(json_extract(title, '$.\"en\"')) like ?", [$search])
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->whereRaw("lower(json_unquote(json_extract(full_name, '$.uk'))) like ?", [$search])
                            ->orWhereRaw("lower(json_unquote(json_extract(full_name, '$.en'))) like ?", [$search]);
                    });
            });
        }

        $projectsByStatus = $query
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn (Project $project) => $project->status->value);

        $columns = [];

        foreach (ProjectStatus::cases() as $status) {
            $columns[$status->value] = $projectsByStatus->get($status->value, new Collection);
        }

        return $columns;
    }

    public function moveProject(int $projectId, string $newStatus): void
    {
        $project = Project::query()->findOrFail($projectId);

        $this->applyStatusChange($project, ProjectStatus::from($newStatus));
    }

    public function startReview(int $projectId): void
    {
        $project = Project::query()->findOrFail($projectId);

        if (app(ProjectWorkflowService::class)->startReview($project)) {
            Notification::make()
                ->title('Проєкт взято в розгляд')
                ->body('Редагування проєкту заблоковано на час модерації.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Не вдалося взяти проєкт у розгляд')
                ->danger()
                ->send();
        }
    }

    private function applyStatusChange(Project $project, ProjectStatus $newStatus): void
    {
        if ($project->status === $newStatus) {
            return;
        }

        $moved = app(ProjectWorkflowService::class)->moveTo($project, $newStatus);

        if ($moved) {
            Notification::make()
                ->title('Статус проєкту оновлено')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Перехід неможливий')
                ->body('Такий перехід статусу заборонено бізнес-правилами.')
                ->danger()
                ->send();
        }
    }
}
