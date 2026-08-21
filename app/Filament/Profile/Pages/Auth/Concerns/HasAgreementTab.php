<?php

namespace App\Filament\Profile\Pages\Auth\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;

trait HasAgreementTab
{
    private function agreementTab(): Tab
    {
        return Tab::make(__('profile_edit.tabs.agreement'))
            ->key('agreement')
            ->icon('heroicon-o-document-text')
            ->schema([
                Section::make(__('profile_edit.sections.agreement.title'))
                    ->description(__('profile_edit.sections.agreement.description'))
                    ->extraAttributes(['class' => 'profile-edit-content'])
                    ->schema([
                        Text::make(__('profile_edit.sections.agreement.signing_note')),
                        Actions::make([
                            Action::make('prepareContract')
                                ->label(__('profile_edit.actions.sign_agreement'))
                                ->icon('heroicon-o-pencil-square')
                                ->action(fn () => $this->prepareContract()),
                        ])->fullWidth(),
                        Text::make(__('profile_edit.sections.agreement.documents_note')),
                        FileUpload::make('profileDocuments')
                            ->label(__('profile_edit.actions.upload_documents'))
                            ->multiple()
                            ->downloadable()
                            ->openable()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(15360)
                            ->disk('public')
                            ->directory('profile_documents')
                            ->panelLayout('grid')
                            ->extraFieldWrapperAttributes([
                                'class' => 'profile-project-stage-documents profile-agreement-documents',
                                'x-init' => <<<'JS'
                                    const markDocumentTypes = () => {
                                        $el.querySelectorAll('.filepond--item').forEach((item) => {
                                            const fileName = item.querySelector('.filepond--file-info-main')?.textContent?.trim()
                                            const hasImagePreview = item.querySelector('.filepond--image-preview') !== null

                                            if (! fileName || hasImagePreview) {
                                                delete item.dataset.fileExtension

                                                return
                                            }

                                            const extension = fileName.includes('.')
                                                ? fileName.split('.').pop()
                                                : 'FILE'

                                            item.dataset.fileExtension = extension.slice(0, 5).toUpperCase()
                                        })
                                    }

                                    const documentPreviewObserver = new MutationObserver(markDocumentTypes)
                                    documentPreviewObserver.observe($el, { childList: true, subtree: true })
                                    markDocumentTypes()
                                    JS,
                            ]),
                        Text::make(__('profile_edit.sections.agreement.optional_note')),
                    ]),
            ]);
    }
}
