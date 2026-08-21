<?php

namespace App\Filament\Profile\Resources\Projects\Concerns;

use App\Models\ImpersonationToken;
use App\Models\Project;
use Filament\Notifications\Notification;
use Illuminate\Support\Js;

trait OpensProjectPreview
{
    protected function openProjectPreview(Project $project): void
    {
        $grant = ImpersonationToken::issue(
            $project->user,
            auth()->user(),
            $project->slug,
            'save_art_project_preview',
        );

        $url = rtrim(config('app.frontend_url'), '/').'/impersonate/'.$grant->token;

        Notification::make()
            ->title(__('profile_projects.publication.preview_opened'))
            ->success()
            ->send();

        // Кнопка синхронно відкриває порожню іменовану вкладку, щоб браузер
        // не заблокував popup після асинхронного Livewire-запиту. Тут лише
        // направляємо вже відкриту вкладку на одноразовий SSO-грант.
        $this->js('window.open('.Js::from($url).', "project-preview")');
    }
}
