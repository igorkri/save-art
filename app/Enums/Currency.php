<?php

namespace App\Enums;

enum Currency: string
{
    case UAH = 'UAH';
    case USD = 'USD';
    case EUR = 'EUR';

    public function icon(): string
    {
        return match ($this) {
            self::UAH => asset('img/ua.webp'),
            self::USD => asset('img/usd.webp'),
            self::EUR => asset('img/eur.webp'),
            default => asset('img/default-currency.webp'),
        };
    }
}

