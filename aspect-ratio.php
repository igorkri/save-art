#!/usr/bin/env php
<?php

/**
 * Консольный скрипт для вычисления соотношения сторон
 * Использование:
 *   php aspect-ratio.php 1920 800
 */

// Проверяем аргументы
if ($argc < 3) {
    echo "Використання: php {$argv[0]} <ширина> <висота>\n";
    exit(1);
}

$width = (int) $argv[1];
$height = (int) $argv[2];

if ($width <= 0 || $height <= 0) {
    echo "Ошибка: ширина и высота должны быть положительными числами\n";
    exit(1);
}

// Функция нахождения НОД (алгоритм Евклида)
function gcd(int $a, int $b): int
{
    return $b === 0 ? $a : gcd($b, $a % $b);
}

// Вычисляем НОД
$gcd = gcd($width, $height);

// Сокращённое соотношение
$ratioW = $width / $gcd;
$ratioH = $height / $gcd;

echo "Розміри: {$width}x{$height}\n";
echo "Співвідношення сторін: {$ratioW}:{$ratioH}\n";
echo "Десяткове співвідношення: " . round($width / $height, 3) . ":1\n";
