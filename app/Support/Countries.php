<?php

namespace App\Support;

use League\ISO3166\ISO3166;
use Locale;

class Countries
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach ((new ISO3166)->all() as $country) {
            $name = Locale::getDisplayRegion("und-{$country['alpha2']}", 'uk');
            $options[$name] = $name;
        }

        asort($options);

        return $options;
    }
}
