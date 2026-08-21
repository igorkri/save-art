<?php

namespace App\Filament\Profile\Pages\Auth\Concerns;

use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;

trait HasSecurityTab
{
    private function securityTab(): Tab
    {
        return Tab::make(__('profile_edit.tabs.security'))
            ->key('security')
            ->icon('heroicon-o-lock-closed')
            ->schema([
                Section::make(__('profile_edit.sections.security.title'))
                    ->description(__('profile_edit.sections.security.description'))
                    ->extraAttributes(['class' => 'profile-edit-content'])
                    ->schema([
                        $this->getEmailFormComponent()
                            ->helperText(__('profile_edit.helpers.email_change')),
                        $this->getCurrentPasswordFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        Actions::make([
                            Action::make('requestProfileDeletion')
                                ->label(__('profile_edit.actions.delete_profile'))
                                ->icon('heroicon-o-trash')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading(__('profile_edit.actions.delete_profile'))
                                ->modalDescription(__('profile_edit.messages.delete_confirmation'))
                                ->action(fn () => $this->requestProfileDeletion()),
                        ])->alignCenter(),
                    ]),
            ]);
    }
}
