<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case New = 'new';
    case Draft = 'draft';
    case Moderation = 'moderation';
    case Announced = 'announced';
    case InProgress = 'in_progress';
    case Paused = 'paused';
    case Completed = 'completed';
    case Sold = 'sold';
    case Rejected = 'rejected';

    /**
     * Отримати назву статусу (підтримує мультимовність)
     */
    public function getLabel(?string $language = 'uk'): string
    {
        $labels = [
            'uk' => match ($this) {
                self::New => 'Новий',
                self::Draft => 'Чернетка',
                self::Moderation => 'На модерації',
                self::Announced => 'Оголошений',
                self::InProgress => 'В роботі',
                self::Paused => 'На паузі',
                self::Completed => 'Завершений',
                self::Sold => 'Проданий',
                self::Rejected => 'Відхилений',
            },
            'en' => match ($this) {
                self::New => 'New',
                self::Draft => 'Draft',
                self::Moderation => 'Under Review',
                self::Announced => 'Announced',
                self::InProgress => 'In Progress',
                self::Paused => 'Paused',
                self::Completed => 'Completed',
                self::Sold => 'Sold',
                self::Rejected => 'Rejected',
            },
        ];

        return $labels[$language] ?? $labels['uk'];
    }

    /**
     * Отримати колір статусу для відображення в UI (Filament)
     */
    public function getColor(): string
    {
        return match ($this) {
            self::New, self::Draft, self::Paused => 'gray',
            self::Moderation => 'warning',
            self::Announced => 'info',
            self::InProgress => 'primary',
            self::Completed, self::Sold => 'success',
            self::Rejected => 'danger',
        };
    }

    /**
     * Іконка статусу для степера
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::New => 'heroicon-o-sparkles',
            self::Draft => 'heroicon-o-pencil-square',
            self::Moderation => 'heroicon-o-clock',
            self::Announced => 'heroicon-o-megaphone',
            self::InProgress => 'heroicon-o-arrow-path',
            self::Paused => 'heroicon-o-pause-circle',
            self::Completed => 'heroicon-o-check-circle',
            self::Sold => 'heroicon-o-currency-dollar',
            self::Rejected => 'heroicon-o-x-circle',
        };
    }

    /**
     * Отримати всі статуси з перекладами
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

    /**
     * Статуси, видимі для гостей та неавторизованих користувачів
     *
     * @return array<self>
     */
    public static function publicStatuses(): array
    {
        return [
            self::Announced,
            self::InProgress,
            self::Paused,
            self::Completed,
            self::Sold,
        ];
    }

    /**
     * Статуси, видимі тільки для власника та модераторів
     *
     * @return array<self>
     */
    public static function privateStatuses(): array
    {
        return [
            self::New,
            self::Draft,
            self::Moderation,
            self::Rejected,
        ];
    }

    /**
     * Чи може проєкт приймати донати
     */
    public function canReceiveDonations(): bool
    {
        return in_array($this, [self::Announced, self::InProgress]);
    }

    /**
     * Чи можна редагувати проєкт повністю (чернетки).
     * Rejected навмисно виключено: за флоу (docs/project-lifecycle-flow.md) відхилення —
     * фінальний стан без повторної подачі на модерацію, єдина дозволена дія — видалення.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::New, self::Draft]);
    }

    /**
     * Чи можна редагувати проєкт частково (опубліковані)
     * Дозволяє редагувати категорію, бюджет, характеристики, етапи та
     * додаткову інформацію. Точний перелік полів перевіряється на межі запису.
     */
    public function isPartiallyEditable(): bool
    {
        return in_array($this, [self::Announced, self::InProgress, self::Paused]);
    }
}
