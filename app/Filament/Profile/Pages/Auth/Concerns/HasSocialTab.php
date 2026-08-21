<?php

namespace App\Filament\Profile\Pages\Auth\Concerns;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;

trait HasSocialTab
{
    private function socialTab(): Tab
    {
        return Tab::make(__('profile_edit.tabs.social'))
            ->key('social')
            ->icon('heroicon-o-share')
            ->schema([
                Section::make(__('profile_edit.sections.social_links.title'))
                    ->description(__('profile_edit.sections.social_links.description'))
                    ->extraAttributes(['class' => 'profile-edit-content'])
                    ->schema($this->socialFields()),
            ]);
    }

    /**
     * @return array<int, TextInput>
     */
    private function socialFields(): array
    {
        return collect($this->socialFieldLabels())->map(
            fn (string $label, string $field): TextInput => TextInput::make("profileSocial.{$field}")
                ->label($label)
                ->url()
                ->maxLength(500),
        )->values()->all();
    }

    /**
     * @return array<string, string>
     */
    private function socialFieldLabels(): array
    {
        return [
            'website' => __('profile_edit.social.website'),
            'facebook' => __('profile_edit.social.facebook'),
            'instagram' => __('profile_edit.social.instagram'),
            'linkedin' => __('profile_edit.social.linkedin'),
            'twitter' => __('profile_edit.social.twitter'),
            //            'telegram' => __('profile_edit.social.telegram'),
            'youtube' => __('profile_edit.social.youtube'),
            //            'youtube_channel' => __('profile_edit.social.youtube_channel'),
            //            'tiktok' => __('profile_edit.social.tiktok'),
            //            'github' => __('profile_edit.social.github'),
            'pinterest' => __('profile_edit.social.pinterest'),
            //            'whatsapp' => __('profile_edit.social.whatsapp'),
            //            'deviantart' => __('profile_edit.social.deviantart'),
        ];
    }
}
