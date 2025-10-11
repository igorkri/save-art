<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

trait ConvertsImages
{
    /**
     * Конвертує зображення у WebP формат із заданими параметрами
     *
     * @param mixed $file Файл для конвертації
     * @param array $options Опції конвертації
     *   - 'width' (int): Ширина зображення (за замовчуванням: null)
     *   - 'height' (int): Висота зображення (за замовчуванням: null)
     *   - 'quality' (int): Якість WebP (0-100, за замовчуванням: 85)
     *   - 'method' (string): Метод зміни розміру ('cover', 'fit', 'resize', за замовчуванням: 'cover')
     *   - 'directory' (string): Директорія для збереження (за замовчуванням: 'images')
     *   - 'disk' (string): Диск для збереження (за замовчуванням: 'public')
     *   - 'filename' (string|null): Кастомне ім'я файлу (за замовчуванням: null - генерується автоматично)
     *   - 'preserve_aspect_ratio' (bool): Зберігати співвідношення сторін для методу 'fit' (за замовчуванням: true)
     * @return string Шлях до збереженого файлу
     */
    public static function convertImageToWebp($file, array $options = []): string
    {
        $defaults = [
            'width' => null,
            'height' => null,
            'quality' => 85,
            'method' => 'cover',
            'directory' => 'images',
            'disk' => 'public',
            'filename' => null,
            'preserve_aspect_ratio' => true,
        ];

        $config = array_merge($defaults, $options);

        // Генеруємо ім'я файлу
        if ($config['filename']) {
            $filename = $config['filename'];
        } else {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = $originalName . '_' . time() . '.webp';
        }

        $path = trim($config['directory'], '/') . '/' . $filename;

        // Створюємо менеджер зображень
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->path());

        // Застосовуємо зміну розміру залежно від методу
        if ($config['width'] || $config['height']) {
            switch ($config['method']) {
                case 'cover':
                    // Обрізає зображення до точного розміру
                    $image->cover($config['width'], $config['height']);
                    break;

                case 'fit':
                    // Вписує зображення в задані розміри зі збереженням пропорцій
                    if ($config['preserve_aspect_ratio']) {
                        $image->scale(
                            width: $config['width'],
                            height: $config['height']
                        );
                    } else {
                        $image->resize($config['width'], $config['height']);
                    }
                    break;

                case 'resize':
                    // Змінює розмір без збереження пропорцій
                    $image->resize($config['width'], $config['height']);
                    break;
            }
        }

        // Конвертуємо у WebP
        $encoded = $image->toWebp($config['quality']);

        // Зберігаємо
        Storage::disk($config['disk'])->put($path, $encoded);

        return $path;
    }

    /**
     * Видаляє файл зображення
     *
     * @param string $path Шлях до файлу
     * @param string $disk Диск (за замовчуванням: 'public')
     * @return bool
     */
    public static function deleteImage(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->delete($path);
    }
}
