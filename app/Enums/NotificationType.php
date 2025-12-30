<?php

namespace App\Enums;

enum NotificationType: string
{
    case Donation = 'donation';
    case Moderation = 'moderation';
    case Message = 'message';
    case System = 'system';
    case ProjectApproved = 'project_approved';
    case ProjectRejected = 'project_rejected';
    case ProjectCompleted = 'project_completed';
    case BonusClaimed = 'bonus_claimed';

    /**
     * Отримати назву типу сповіщення українською
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Donation => 'Донат',
            self::Moderation => 'Модерація',
            self::Message => 'Повідомлення',
            self::System => 'Системне',
            self::ProjectApproved => 'Проєкт схвалено',
            self::ProjectRejected => 'Проєкт відхилено',
            self::ProjectCompleted => 'Проєкт завершено',
            self::BonusClaimed => 'Бонус отримано',
        };
    }

    /**
     * Отримати іконку для типу сповіщення
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::Donation => 'heroicon-o-currency-dollar',
            self::Moderation => 'heroicon-o-shield-check',
            self::Message => 'heroicon-o-chat-bubble-left-right',
            self::System => 'heroicon-o-cog-6-tooth',
            self::ProjectApproved => 'heroicon-o-check-circle',
            self::ProjectRejected => 'heroicon-o-x-circle',
            self::ProjectCompleted => 'heroicon-o-flag',
            self::BonusClaimed => 'heroicon-o-gift',
        };
    }

    /**
     * Отримати колір для типу сповіщення
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Donation => 'success',
            self::Moderation => 'warning',
            self::Message => 'info',
            self::System => 'gray',
            self::ProjectApproved => 'success',
            self::ProjectRejected => 'danger',
            self::ProjectCompleted => 'success',
            self::BonusClaimed => 'info',
        };
    }

    /**
     * Отримати всі опції для select
     *
     * @return array<string, string>
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }
}
