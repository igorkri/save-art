<?php

namespace App\Filament\Profile\Pages;

use App\Enums\SignService;
use App\Models\ProfileDocument;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class Documents extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function getTitle(): string
    {
        return __('profile_edit.tabs.documents');
    }

    public static function getNavigationLabel(): string
    {
        return __('profile_edit.tabs.documents');
    }

    public function mount(): void
    {
        $this->form->fill([
            'profileDocuments' => $this->getUser()->profileDocuments()->pluck('file_path')->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('profile_edit.sections.documents.title'))
                    ->description(__('profile_edit.sections.documents.description'))
                    ->schema([
                        FileUpload::make('profileDocuments')
                            ->label(__('profile_edit.fields.files'))
                            ->multiple()
                            ->downloadable()
                            ->openable()
                            ->maxSize(15360)
                            ->disk('public')
                            ->directory('profile_documents')
                            ->panelLayout('grid')
//                            ->imagePreviewHeight('160')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->key('form-actions'),
                    ]),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::auth/pages/edit-profile.form.actions.save.label'))
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->syncProfileDocuments($this->getUser(), is_array($data['profileDocuments'] ?? []) ? $data['profileDocuments'] : []);

        Notification::make()
            ->success()
            ->title(__('profile_edit.saved_notification'))
            ->send();
    }

    private function getUser(): User
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        return $user;
    }

    /**
     * @param  list<string>  $documentPaths
     */
    private function syncProfileDocuments(User $user, array $documentPaths): void
    {
        $documentPaths = array_values(array_unique(array_filter($documentPaths)));
        $existingDocuments = $user->profileDocuments()->get()->keyBy('file_path');
        $pathsToDelete = $existingDocuments->keys()->diff($documentPaths);
        $pathsToAdd = collect($documentPaths)->diff($existingDocuments->keys());

        foreach ($pathsToAdd as $filePath) {
            if (! Storage::disk('public')->exists($filePath)) {
                continue;
            }

            $hash = hash_file('sha256', Storage::disk('public')->path($filePath));

            if ($hash === false) {
                throw ValidationException::withMessages([
                    'data.profileDocuments' => __('profile_edit.messages.document_unreadable'),
                ]);
            }
            $duplicate = ProfileDocument::query()->where('hash', $hash)->first();

            if ($duplicate !== null) {
                Storage::disk('public')->delete($filePath);

                throw ValidationException::withMessages([
                    'data.profileDocuments' => __('profile_edit.messages.document_duplicate'),
                ]);
            }

            $user->profileDocuments()->create([
                'file_path' => $filePath,
                'hash' => $hash,
                'sign_status' => 'pending',
                'service' => SignService::Diia->value,
            ]);
        }

        if ($pathsToDelete->isEmpty()) {
            return;
        }

        $user->profileDocuments()->whereIn('file_path', $pathsToDelete)->delete();

        DB::afterCommit(fn () => Storage::disk('public')->delete($pathsToDelete->all()));
    }
}
