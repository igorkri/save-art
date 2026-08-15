<?php

namespace App\Policies;

use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\UserRole;

class ProjectPolicy
{
    /**
     * Адміністратори мають повний доступ
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return null;
    }

    /**
     * Будь-який авторизований користувач може переглядати список своїх проєктів
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Користувач може переглядати свій проєкт або публічний
     */
    public function view(User $user, Project $project): bool
    {
        // Власник може переглядати
        if ($project->user_id === $user->id) {
            return true;
        }

        // Публічні проєкти може переглядати будь-хто
        return $project->isPublic();
    }

    /**
     * Будь-який авторизований користувач може створювати проєкти
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Тільки власник може редагувати проєкт, і тільки якщо він (повністю чи
     * частково) редагований. Проєкт у черзі на модерацію (status_moderation = pending)
     * все ще редагується, але щойно модератор бере його в розгляд (processing) —
     * редагування блокується. Опубліковані проєкти (Announced/InProgress/Paused)
     * доступні для часткового редагування (docs/project-lifecycle-flow.md) — саме
     * так дозволяється дійти до дії "Завершити проєкт" для InProgress.
     * Completed/Sold теж лишаються доступними для відкриття — виключно заради
     * "Фінального результату", решта полів там заблокована в ProjectForm
     * (ProjectForm::isLockedExceptFinalResult()).
     */
    public function update(User $user, Project $project): bool
    {
        if ($project->user_id !== $user->id) {
            return false;
        }

        return $project->isEditable()
            || $project->isPartiallyEditable()
            || in_array($project->status, [ProjectStatus::Completed, ProjectStatus::Sold], true)
            || ($project->status === ProjectStatus::Moderation && $project->status_moderation !== ModerationStatus::Processing);
    }

    /**
     * Тільки власник може видаляти чернетки
     */
    public function delete(User $user, Project $project): bool
    {
        return $project->user_id === $user->id && $project->status === ProjectStatus::Draft;
    }

    /**
     * Тільки адміни (через before)
     */
    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    /**
     * Тільки адміни (через before)
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }

    /**
     * Власник може відправити на модерацію
     */
    public function submit(User $user, Project $project): bool
    {
        return $project->user_id === $user->id
            && in_array($project->status, [ProjectStatus::Draft, ProjectStatus::Rejected]);
    }

    /**
     * Власник може завершити проєкт в роботі
     */
    public function complete(User $user, Project $project): bool
    {
        return $project->user_id === $user->id
            && $project->status === ProjectStatus::InProgress;
    }

    /**
     * Власник може керувати етапами
     */
    public function manageStages(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }

    /**
     * Власник може керувати бонусами
     */
    public function manageBonuses(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }
}
