<?php

namespace App\Enums;

/**
 * Sign service enum for document signing.
 */
enum SignService: string
{
    case Diia = 'diia';
    case Vchasno = 'vchasno';
    case Iit = 'iit';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Diia => 'Дія',
            self::Vchasno => 'Вчасно',
            self::Iit => 'ІІТ',
            self::Manual => 'Ручний підпис',
        };
    }
}
