<?php

namespace App\Enums;

enum ParameterType: string
{
    case Custom = 'custom';
    case List = 'list';

    /**
     * Отримати назву типу українською
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Custom => 'Довільне значення',
            self::List => 'Список значень',
        };
    }

    /**
     * Отримати всі типи з перекладами
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
