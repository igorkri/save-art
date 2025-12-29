<?php

namespace App\Enums;

enum DonationStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    /**
     * Отримати назву статусу українською
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Очікує оплати',
            self::Paid => 'Оплачено',
            self::Failed => 'Помилка оплати',
            self::Refunded => 'Повернено',
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
}
