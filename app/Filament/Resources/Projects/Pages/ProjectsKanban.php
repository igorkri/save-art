<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\ImpersonationToken;
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

    /**
     * За замовчуванням Filament веде хлібну крихту ресурсу на index-сторінку
     * (список). Канбан тут — основна робоча сторінка проєктів, тож крихта
     * "Проєкти" має вести саме сюди, а не на список.
     *
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            static::getUrl() => ProjectResource::getBreadcrumb(),
            $this->getBreadcrumb(),
        ];
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
            ->modalHeading(fn (array $arguments): string => $this->findProject($arguments)->title ?: 'Проєкт')
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
            ->with([
                'user.profileLegal',
                'artCategory',
                'stages',
                'bonuses',
                'donations.user',
                'donations.bonus',
                'projectParameters.parameter',
                'projectParameters.parameterValue',
            ])
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
                        $userQuery->whereRaw('lower(full_name) like ?', [$search]);
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

    /**
     * Увійти на фронтенд під автором проєкту в новій вкладці (одразу на цей
     * проєкт у його кабінеті). Доступно лише Admin/Developer — саме вони
     * мають доступ до цієї Filament-панелі.
     */
    public function impersonateAuthor(int $projectId): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $project = Project::query()->with('user')->findOrFail($projectId);
        $grant = ImpersonationToken::issue($project->user, auth()->user(), $project->slug);
        $url = rtrim(config('app.frontend_url'), '/').'/impersonate/'.$grant->token;

        $this->js('window.open('.json_encode($url).', "_blank")');
    }

    /**
     * Те саме, що impersonateAuthor(), але веде на art-ua-info замість
     * save-art (React SPA) — окремий фронтенд з іншою структурою роутів.
     */
    public function impersonateAuthorArtUaInfo(int $projectId): void
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $project = Project::query()->with('user')->findOrFail($projectId);
        $grant = ImpersonationToken::issue($project->user, auth()->user(), $project->slug, 'art_ua_info');
        $url = rtrim(config('services.art_ua_info_frontend_url'), '/').'/impersonate/'.$grant->token;

        $this->js('window.open('.json_encode($url).', "_blank")');
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
