<?php

namespace App\Services;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ModerationService
{
    public function __construct(
        private ProjectWorkflowService $workflowService
    ) {}

    /**
     * Отримати проєкти на модерації
     *
     * @return Collection<int, Project>
     */
    public function getPendingProjects(): Collection
    {
        return Project::query()
            ->where('status', ProjectStatus::Moderation)
            ->where('status_moderation', ModerationStatus::Pending)
            ->with(['user', 'stages', 'bonuses'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Отримати кількість проєктів на модерації
     */
    public function getPendingCount(): int
    {
        return Project::query()
            ->where('status', ProjectStatus::Moderation)
            ->where('status_moderation', ModerationStatus::Pending)
            ->count();
    }

    /**
     * Схвалити проєкт
     */
    public function approveProject(Project $project, User $moderator): bool
    {
        if (! $this->canModerate($moderator)) {
            return false;
        }

        return $this->workflowService->approve($project);
    }

    /**
     * Відхилити проєкт
     */
    public function rejectProject(Project $project, User $moderator, ?string $reason = null): bool
    {
        if (! $this->canModerate($moderator)) {
            return false;
        }

        return $this->workflowService->reject($project, $reason);
    }

    /**
     * Повернути проєкт на доопрацювання
     */
    public function returnForRevision(Project $project, User $moderator, ?string $comment = null): bool
    {
        if (! $this->canModerate($moderator)) {
            return false;
        }

        // Используем workflow service для правильного перехода статуса
        $success = $this->workflowService->returnToDraft($project);

        if ($success && $comment) {
            $project->update([
                'moderation_comment' => $comment,
            ]);
        }

        return $success;
    }

    /**
     * Перевірити, чи може користувач модерувати
     */
    public function canModerate(User $user): bool
    {
        return $user->role->canModerate();
    }

    /**
     * Отримати статистику модерації
     *
     * @return array{
     *     pending: int,
     *     approved_today: int,
     *     rejected_today: int,
     *     total_moderated_today: int
     * }
     */
    public function getStatistics(): array
    {
        $today = now()->startOfDay();

        $pending = Project::query()
            ->where('status', ProjectStatus::Moderation)
            ->where('status_moderation', ModerationStatus::Pending)
            ->count();

        $approvedToday = Project::query()
            ->where('status_moderation', ModerationStatus::Approved)
            ->where('announced_at', '>=', $today)
            ->count();

        $rejectedToday = Project::query()
            ->where('status', ProjectStatus::Rejected)
            ->where('updated_at', '>=', $today)
            ->count();

        return [
            'pending' => $pending,
            'approved_today' => $approvedToday,
            'rejected_today' => $rejectedToday,
            'total_moderated_today' => $approvedToday + $rejectedToday,
        ];
    }

    /**
     * Отримати історію модерації проєкту
     *
     * @return array<array{status: string, date: string}>
     */
    public function getProjectModerationHistory(Project $project): array
    {
        // Це спрощена реалізація, для повної потрібна окрема таблиця модерації
        $history = [];

        if ($project->announced_at) {
            $history[] = [
                'status' => 'approved',
                'date' => $project->announced_at->toIso8601String(),
            ];
        }

        if ($project->status === ProjectStatus::Rejected) {
            $history[] = [
                'status' => 'rejected',
                'date' => $project->updated_at->toIso8601String(),
                'reason' => $project->rejection_reason,
            ];
        }

        return $history;
    }
}
