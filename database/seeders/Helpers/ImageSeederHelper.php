<?php

namespace Database\Seeders\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageSeederHelper
{
    /**
     * Завантажує зображення з URL як файл
     */
    public static function downloadImage(string $url, string $directory): ?string
    {
        try {
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                // Отримуємо розширення
                $extension = self::getExtensionFromUrl($url) ?? 'jpg';

                // Створюємо тимчасовий файл
                $tempPath = tempnam(sys_get_temp_dir(), 'seed_image_');
                file_put_contents($tempPath, $response->body());

                // Генеруємо унікальне ім'я файлу
                $filename = Str::random(40).'.'.$extension;

                // Створюємо UploadedFile об'єкт
                $uploadedFile = new UploadedFile(
                    $tempPath,
                    $filename,
                    mime_content_type($tempPath),
                    null,
                    true
                );

                // Зберігаємо файл через Storage
                $path = Storage::disk('public')->putFileAs(
                    $directory,
                    $uploadedFile,
                    $filename
                );

                // Видаляємо тимчасовий файл
                @unlink($tempPath);

                return $path;
            }
        } catch (\Exception $e) {
            // Якщо не вдалось завантажити - повертаємо null
        }

        return null;
    }

    /**
     * Отримує розширення файлу з URL
     */
    private static function getExtensionFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension ?: 'jpg';
    }

    /**
     * Завантажує зображення для проєкту з Picsum
     */
    public static function getProjectCover(string $category, int $width = 1200, int $height = 800): ?string
    {
        // Використовуємо Picsum Photos (швидше і надійніше)
        // Додаємо random query щоб отримувати різні зображення
        $random = rand(1, 1000);
        $url = "https://picsum.photos/{$width}/{$height}?random={$random}";

        return self::downloadImage($url, 'projects/covers');
    }

    /**
     * Завантажує аватар користувача
     */
    public static function getUserAvatar(string $gender = 'random'): ?string
    {
        // Використовуємо Picsum Photos для портретів
        $random = rand(1, 1000);
        $url = "https://picsum.photos/400/400?random={$random}";

        return self::downloadImage($url, 'avatars');
    }

    /**
     * Завантажує логотип компанії
     */
    public static function getCompanyLogo(): ?string
    {
        $random = rand(1, 1000);
        $url = "https://picsum.photos/400/400?random={$random}";

        return self::downloadImage($url, 'logos');
    }

    /**
     * Створює placeholder зображення (якщо не потрібно завантажувати)
     */
    public static function createPlaceholder(int $width, int $height, string $path): string
    {
        // Використовуємо через picsum (швидше ніж Unsplash)
        $url = "https://picsum.photos/{$width}/{$height}";

        return self::downloadImage($url, $path) ?? '';
    }
}
