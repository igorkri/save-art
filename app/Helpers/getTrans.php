<?php

if (!function_exists('getTrans')) {
    /**
     * Получить перевод из json-поля с поддержкой 'ua' для украинского языка.
     *
     * @param array|null $json
     * @param string $default
     * @return string
     */
    function getTrans($json, $default = 'ua') {
        if (!is_array($json)) {
            $json = json_decode($json, true);
        }
        $lang = app()->getLocale() === 'uk' ? 'ua' : app()->getLocale();
        return $json[$lang] ?? $json[$default] ?? '';
    }
}
