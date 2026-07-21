<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Models\ImpersonationToken;
use App\Models\Message;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    /**
     * Захист від обходу disabled-полів у формі (напр. через DevTools):
     * усім, крім Developer, дозволено змінювати лише статуси проєкту.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! ProjectForm::canFullyEdit()) {
            $data = array_merge(
                $this->record->only(array_keys($data)),
                [
                    'status' => $data['status'] ?? $this->record->status,
                    'status_moderation' => $data['status_moderation'] ?? $this->record->status_moderation,
                ]
            );
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('impersonate')
                ->label('Увійти як автор')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()->isAdmin())
                ->requiresConfirmation()
                ->modalHeading('Увійти на сайт під автором проєкту')
                ->modalDescription('Відкриє фронтенд у новій вкладці, авторизованим під автором, одразу на цей проєкт у його кабінеті. Посилання одноразове й діє 2 хвилини.')
                ->modalSubmitActionLabel('Увійти')
                ->action(function () {
                    $grant = ImpersonationToken::issue($this->record->user, auth()->user(), $this->record->slug);
                    $url = rtrim(config('app.frontend_url'), '/').'/impersonate/'.$grant->token;

                    $this->js('window.open('.json_encode($url).', "_blank")');
                }),
            Action::make('sendMessage')
                ->label('Написати автору')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('info')
                ->form([
                    TextInput::make('subject')
                        ->label('Тема')
                        ->default(fn () => 'Щодо проєкту: '.($this->record->title['uk'] ?? $this->record->title['en'] ?? ''))
                        ->maxLength(255),
                    Textarea::make('content')
                        ->label('Повідомлення')
                        ->required()
                        ->rows(5)
                        ->placeholder('Введіть текст повідомлення...'),
                ])
                ->action(function (array $data): void {
                    Message::create([
                        'user_id' => $this->record->user_id,
                        'admin_id' => Auth::id(),
                        'project_id' => $this->record->id,
                        'subject' => $data['subject'],
                        'content' => $data['content'],
                        'direction' => 'admin_to_user',
                    ]);

                    Notification::make()
                        ->title('Повідомлення надіслано')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
