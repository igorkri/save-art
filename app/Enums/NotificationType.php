<?php

namespace App\Enums;

enum NotificationType: string
{
    case Donation = 'donation';
    case DonationReceived = 'donation_received';
    case DonationMade = 'donation_made';
    case Moderation = 'moderation';
    case ModerationPending = 'moderation_pending';
    case ModerationFailed = 'moderation_failed';
    case Message = 'message';
    case System = 'system';
    case ProjectApproved = 'project_approved';
    case ProjectRejected = 'project_rejected';
    case ProjectCompleted = 'project_completed';
    case ProjectFundingComplete = 'project_funding_complete';
    case BonusClaimed = 'bonus_claimed';
    case ContactRequest = 'contact_request';
    case ContactProvided = 'contact_provided';
    case ContactRejected = 'contact_rejected';
    case Blocked = 'blocked';
    case Unblocked = 'unblocked';

    /**
     * Отримати назву типу сповіщення українською
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Donation => 'Донат',
            self::DonationReceived => 'Донат отримано',
            self::DonationMade => 'Донат зроблено',
            self::Moderation => 'Модерація',
            self::ModerationPending => 'На модерації',
            self::ModerationFailed => 'Модерацію не пройдено',
            self::Message => 'Повідомлення',
            self::System => 'Системне',
            self::ProjectApproved => 'Проєкт схвалено',
            self::ProjectRejected => 'Проєкт відхилено',
            self::ProjectCompleted => 'Проєкт завершено',
            self::ProjectFundingComplete => 'Збір завершено',
            self::BonusClaimed => 'Бонус отримано',
            self::ContactRequest => 'Запит контактів',
            self::ContactProvided => 'Контакти надано',
            self::ContactRejected => 'Контакти відхилено',
            self::Blocked => 'Профіль заблоковано',
            self::Unblocked => 'Профіль розблоковано',
        };
    }

    /**
     * Отримати іконку для типу сповіщення
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::Donation, self::DonationReceived, self::DonationMade => 'heroicon-o-currency-dollar',
            self::Moderation, self::ModerationPending, self::ModerationFailed => 'heroicon-o-shield-check',
            self::Message => 'heroicon-o-chat-bubble-left-right',
            self::System => 'heroicon-o-cog-6-tooth',
            self::ProjectApproved => 'heroicon-o-check-circle',
            self::ProjectRejected => 'heroicon-o-x-circle',
            self::ProjectCompleted, self::ProjectFundingComplete => 'heroicon-o-flag',
            self::BonusClaimed => 'heroicon-o-gift',
            self::ContactRequest, self::ContactProvided, self::ContactRejected => 'heroicon-o-identification',
            self::Blocked => 'heroicon-o-lock-closed',
            self::Unblocked => 'heroicon-o-lock-open',
        };
    }

    /**
     * Отримати колір для типу сповіщення
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Donation, self::DonationReceived, self::DonationMade => 'success',
            self::Moderation, self::ModerationPending => 'warning',
            self::ModerationFailed => 'danger',
            self::Message => 'info',
            self::System => 'gray',
            self::ProjectApproved => 'success',
            self::ProjectRejected => 'danger',
            self::ProjectCompleted, self::ProjectFundingComplete => 'success',
            self::BonusClaimed => 'info',
            self::ContactRequest => 'warning',
            self::ContactProvided => 'success',
            self::ContactRejected => 'danger',
            self::Blocked => 'danger',
            self::Unblocked => 'success',
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
