<?php

namespace App\Enums;

enum DocumentSignStatus: string
{
    case Pending = 'pending';
    case Signed = 'signed';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Очікує підпису',
            self::Signed => 'Підписано',
            self::Rejected => 'Відхилено',
            self::Expired => 'Залишило терміну дії',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Signed => 'success',
            self::Rejected => 'danger',
            self::Expired => 'gray',
        };
    }
}
