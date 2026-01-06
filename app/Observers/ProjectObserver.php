<?php

namespace App\Observers;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Services\NotificationService;

class ProjectObserver
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle the Project "updated" event.
     * Надсилаємо сповіщення при зміні статусу
     */
    public function updated(Project $project): void
    {
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
            ProjectStatus::Announced => $this->notificationService->notifyProjectApproved($project),
            ProjectStatus::Rejected => $this->handleRejection($project),
            ProjectStatus::Completed => $this->notificationService->notifyProjectCompleted($project),
            default => null,
        };
    }

    /**
     * Обробка відхилення проекту
     */
    private function handleRejection(Project $project): void
    {
        $reason = $project->moderation_comment ?? null;

        // Перевіряємо чи це перше відхилення (можна виправити) чи остаточне
        $rejectionCount = $project->moderation_history_count ?? 1;

        if ($rejectionCount >= 3) {
            // Остаточне відхилення
            $this->notificationService->notifyProjectRejected($project, $reason);
        } else {
            // Можна виправити
            $this->notificationService->notifyProjectModerationFailed($project, $reason);
        }
    }
}
