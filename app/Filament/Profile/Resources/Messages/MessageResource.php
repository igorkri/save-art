<?php

namespace App\Filament\Profile\Resources\Messages;

use App\Filament\Profile\Resources\Messages\Pages\CreateMessage;
use App\Filament\Profile\Resources\Messages\Pages\ListMessages;
use App\Filament\Profile\Resources\Messages\Schemas\MessageForm;
use App\Filament\Profile\Resources\Messages\Tables\MessagesTable;
use App\Models\Message;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 0;

    public static function getModelLabel(): string
    {
        return __('profile_messages.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('profile_messages.model.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('profile_messages.model.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->fromAdmin()->unread()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return MessageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MessagesTable::configure($table);
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessages::route('/'),
            'create' => CreateMessage::route('/create'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }
}
