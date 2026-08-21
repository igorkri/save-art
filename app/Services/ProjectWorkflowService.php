<?php

namespace App\Services;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Enums\StageStatus;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectWorkflowService
{
    /**
     * Дозволені переходи статусів
     *
     * @var array<string, array<string>>
     */
    private array $allowedTransitions = [
        ProjectStatus::New->value => [
            ProjectStatus::Draft->value,
        ],
        ProjectStatus::Draft->value => [
            ProjectStatus::Moderation->value,
        ],
        ProjectStatus::Moderation->value => [
            ProjectStatus::Announced->value,
            ProjectStatus::Rejected->value,
            ProjectStatus::Draft->value,
        ],
        // Rejected — фінальний стан (docs/project-lifecycle-flow.md): жодних переходів назад,
        // навіть для модератора через канбан-дошку. Єдина дозволена дія — видалення проєкту.
        ProjectStatus::Rejected->value => [],
        ProjectStatus::Announced->value => [
            ProjectStatus::InProgress->value,
            ProjectStatus::Paused->value,
        ],
        ProjectStatus::InProgress->value => [
            ProjectStatus::Completed->value,
            ProjectStatus::Paused->value,
            ProjectStatus::Sold->value,
        ],
        ProjectStatus::Paused->value => [
            ProjectStatus::InProgress->value,
            ProjectStatus::Announced->value,
        ],
        ProjectStatus::Completed->value => [
            ProjectStatus::Sold->value,
        ],
        ProjectStatus::Sold->value => [],
    ];

    /**
     * Перевірити, чи можливий перехід статусу
     */
    public function canTransition(Project $project, ProjectStatus $newStatus): bool
    {
        $currentStatus = $project->status->value;
        $allowed = $this->allowedTransitions[$currentStatus] ?? [];

        return in_array($newStatus->value, $allowed);
    }

    /**
     * Отримати дозволені переходи для проєкту
     *
     * @return array<ProjectStatus>
     */
    public function getAllowedTransitions(Project $project): array
    {
        $currentStatus = $project->status->value;
        $allowed = $this->allowedTransitions[$currentStatus] ?? [];

        return array_map(
            fn (string $status) => ProjectStatus::from($status),
            $allowed
        );
    }

    /**
     * Відправити проєкт на модерацію
     */
    public function submitForModeration(Project $project): bool
    {
        if (! $this->canTransition($project, ProjectStatus::Moderation)) {
            return false;
        }

        return DB::transaction(function () use ($project) {
            $project->update([
                'status' => ProjectStatus::Moderation,
                'status_moderation' => ModerationStatus::Pending,
            ]);

            return true;
        });
    }

    /**
     * Модератор бере проєкт у розгляд: переводить з черги (Pending) у активну
     * модерацію (Processing), після чого проєкт стає недоступним для редагування.
     */
    public function startReview(Project $project): bool
    {
        if ($project->status !== ProjectStatus::Moderation) {
            return false;
        }

        if ($project->status_moderation !== ModerationStatus::Pending) {
            return false;
        }

        $project->update([
            'status_moderation' => ModerationStatus::Processing,
        ]);

        return true;
    }

    /**
     * Схвалити проєкт (модератором)
     */
    public function approve(Project $project): bool
    {
        if (! $this->canTransition($project, ProjectStatus::Announced)
            || $project->status_moderation !== ModerationStatus::Processing) {
            return false;
        }

        return DB::transaction(function () use ($project) {
            $project->update([
                'status' => ProjectStatus::Announced,
                'status_moderation' => ModerationStatus::Approved,
                'announced_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Відхилити проєкт (модератором)
     */
    public function reject(Project $project, ?string $reason = null): bool
    {
        if (! $this->canTransition($project, ProjectStatus::Rejected)
            || $project->status_moderation !== ModerationStatus::Processing) {
            return false;
        }

        return DB::transaction(function () use ($project, $reason) {
            $project->update([
                'status' => ProjectStatus::Rejected,
                'status_moderation' => ModerationStatus::Rejected,
                'rejection_reason' => $reason,
                // ProjectObserver::handleRejection() бере причину саме звідси, а не з
                // rejection_reason, тож дублюємо значення, інакше сповіщення про
                // відхилення втратить причину.
                'moderation_comment' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Повернути проєкт до чернетки
     */
    public function returnToDraft(Project $project): bool
    {
        if (! $this->canTransition($project, ProjectStatus::Draft)) {
            return false;
        }

        $project->update([
            'status' => ProjectStatus::Draft,
            // Колонка status_moderation в БД NOT NULL (без дефолтного null) — при поверненні
            // в чернетку скидаємо на 'pending', як і у всіх інших чернеток, а не на null.
            'status_moderation' => ModerationStatus::Pending,
        ]);

        return true;
    }

    /**
     * Розпочати роботу над проєктом (збір завершено)
     */
    public function startWork(Project $project): bool
    {
        if (! $this->canTransition($project, ProjectStatus::InProgress)) {
            return false;
        }

        $project->update([
            'status' => ProjectStatus::InProgress,
        ]);

        return true;
    }

    /**
     * Поставити проєкт на паузу
     */
    public function pause(Project $project): bool
    {
        if (! $this->canTransition($project, ProjectStatus::Paused)) {
            return false;
        }

        $project->update([
            'status' => ProjectStatus::Paused,
        ]);

        return true;
    }

    /**
     * Відновити роботу над проєктом
     */
    public function resume(Project $project): bool
    {
        $currentStatus = $project->status;

        if ($currentStatus === ProjectStatus::Paused) {
            // Визначаємо, до якого статусу повернутись
            $hasBudgetGoal = $project->budget_collected >= $project->budget_goal;

            $newStatus = $hasBudgetGoal
                ? ProjectStatus::InProgress
                : ProjectStatus::Announced;

            if (! $this->canTransition($project, $newStatus)) {
                return false;
            }

            $project->update([
                'status' => $newStatus,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Завершити проєкт
     */
    public function complete(Project $project): bool
    {
        if (! $this->canTransition($project, ProjectStatus::Completed)) {
            return false;
        }

        if (blank($project->final_result)) {
            return false;
        }

        if ($project->stages()->where('status', '!=', StageStatus::Completed->value)->exists()) {
            return false;
        }

        $project->update([
            'status' => ProjectStatus::Completed,
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Позначити проєкт як проданий
     */
    public function markAsSold(Project $project): bool
    {
        if (! $this->canTransition($project, ProjectStatus::Sold)) {
            return false;
        }

        $project->update([
            'status' => ProjectStatus::Sold,
        ]);

        return true;
    }

    /**
     * Перевести проєкт у вказаний статус, підібравши відповідний бізнес-метод
     * (для канбан-дошки та інших місць, де статус обирається довільно).
     *
     * Примітка: для Paused -> Announced/InProgress завжди викликається resume(),
     * оскільки сама resume() вирішує, куди повернути проєкт, залежно від зібраного бюджету —
     * фактичний результуючий статус може відрізнятись від запитаного.
     */
    public function moveTo(Project $project, ProjectStatus $newStatus): bool
    {
        if ($project->status === ProjectStatus::Paused
            && in_array($newStatus, [ProjectStatus::Announced, ProjectStatus::InProgress], true)) {
            return $this->resume($project);
        }

        return match ($newStatus) {
            ProjectStatus::Moderation => $this->submitForModeration($project),
            ProjectStatus::Announced => $this->approve($project),
            ProjectStatus::Rejected => $this->reject($project),
            ProjectStatus::Draft => $this->returnToDraft($project),
            ProjectStatus::InProgress => $this->startWork($project),
            ProjectStatus::Paused => $this->pause($project),
            ProjectStatus::Completed => $this->complete($project),
            ProjectStatus::Sold => $this->markAsSold($project),
            default => false,
        };
    }
}
