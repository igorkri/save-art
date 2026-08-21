<?php

namespace App\Observers;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Observers\Concerns\DeletesReplacedFile;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectObserver
{
    use DeletesReplacedFile;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle the Project "updated" event.
     * Надсилаємо сповіщення при зміні статусу, синхронізуємо статуси та прибираємо стару обкладинку.
     */
    public function updated(Project $project): void
    {
        $this->deleteReplacedFile($project, 'cover');

        // Обрабатываем изменения статуса модерации
        if ($project->wasChanged('status_moderation')) {
            $this->handleModerationStatusChange($project);
        }

        // Перевіряємо чи змінився статус
        if (! $project->wasChanged('status')) {
            return;
        }

        $oldStatus = $project->getOriginal('status');
        $newStatus = $project->status;

        // Конвертуємо в enum якщо це рядок
        if (is_string($newStatus)) {
            $newStatus = ProjectStatus::tryFrom($newStatus);
        }

        if (! $newStatus) {
            return;
        }

        match ($newStatus) {
            ProjectStatus::Moderation => $this->notificationService->notifyProjectModerationPending($project),
            ProjectStatus::Announced => $this->notificationService->notifyProjectApproved($project),
            ProjectStatus::Rejected => $this->handleRejection($project),
            ProjectStatus::Completed => $this->notificationService->notifyProjectCompleted($project),
            default => null,
        };
    }

    /**
     * Обробка зміни статусу модерації
     * Синхронізує основний статус проекту зі статусом модерації
     */
    private function handleModerationStatusChange(Project $project): void
    {
        $oldModerationStatus = $project->getOriginal('status_moderation');
        $newModerationStatus = $project->status_moderation;

        // Конвертуємо в enum якщо це рядок
        if (is_string($newModerationStatus)) {
            $newModerationStatus = ModerationStatus::tryFrom($newModerationStatus);
        }

        if (! $newModerationStatus) {
            return;
        }

        // Если проект находится на модерации, то изменяем основной статус согласно результату модерации
        if ($project->status === ProjectStatus::Moderation) {
            match ($newModerationStatus) {
                ModerationStatus::Approved => $this->updateProjectStatus($project, ProjectStatus::Announced),
                ModerationStatus::Rejected => $this->updateProjectStatus($project, ProjectStatus::Rejected),
                default => null,
            };
        }
    }

    /**
     * Обновить статус проекта без triggering observer снова
     */
    private function updateProjectStatus(Project $project, ProjectStatus $newStatus): void
    {
        // Используем query builder чтобы избежать рекурсивного вызова observer
        Project::where('id', $project->id)->update([
            'status' => $newStatus,
            'announced_at' => $newStatus === ProjectStatus::Announced ? now() : null,
        ]);

        // Обновляем модель в памяти
        $project->status = $newStatus;
        if ($newStatus === ProjectStatus::Announced) {
            $project->announced_at = now();
        }
    }

    /**
     * Handle the Project "force deleted" event.
     * Прибираємо обкладинку з диска при остаточному видаленні проєкту.
     */
    public function forceDeleted(Project $project): void
    {
        $cover = $project->cover;

        if (blank($cover) || Str::startsWith($cover, ['http://', 'https://', 'data:image/'])) {
            return;
        }

        Storage::disk('public')->delete($cover);
    }

    /**
     * Обробка відхилення проекту
     */
    private function handleRejection(Project $project): void
    {
        $reason = $project->moderation_comment ?? null;

        // Rejected є остаточним станом. Для виправлень модератор має окрему дію
        // "Повернути на доопрацювання", яка переводить проєкт у Draft.
        $this->notificationService->notifyProjectRejected($project, $reason);
    }
}
