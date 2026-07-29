<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Сервіс для обробки зображень (Base64 конвертація)
 */
class ImageProcessingService
{
    /**
     * Перевіряє чи є рядок Base64 зображенням
     */
    public function isBase64Image(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        return preg_match('/^data:image\/(\w+);base64,/', $value) === 1;
    }

    /**
     * Обробляє Base64 обкладинку проєкту та зберігає як файл
     */
    public function processCover(?string $cover, ?string $oldCover = null): ?string
    {
        if (empty($cover)) {
            return null;
        }

        // Якщо це не Base64 - повертаємо як є (вже збережений шлях)
        if (! $this->isBase64Image($cover)) {
            return $cover;
        }

        // Видаляємо старий файл, якщо є
        if ($oldCover) {
            Storage::disk('public')->delete($oldCover);
        }

        return $this->saveBase64Image($cover, 'projects/covers');
    }

    /**
     * Обробляє content_blocks (або final_result — той самий формат блоків) та
     * конвертує Base64 зображення в файли.
     *
     * @param  array<int, array<string, mixed>>|null  $contentBlocks
     * @return array<int, array<string, mixed>>|null
     */
    public function processContentBlocks(?array $contentBlocks, ?array $oldContentBlocks = null, string $directory = 'projects/content-blocks'): ?array
    {
        if (empty($contentBlocks)) {
            return $contentBlocks;
        }

        // Збираємо старі шляхи до зображень для видалення
        $oldImagePaths = $this->extractImagePaths($oldContentBlocks);
        $usedPaths = [];

        foreach ($contentBlocks as $index => $block) {
            if (! isset($block['type']) || $block['type'] !== 'image') {
                continue;
            }

            $image = $block['image'] ?? null;

            if (empty($image)) {
                continue;
            }

            // Якщо це Base64 - конвертуємо в файл
            if ($this->isBase64Image($image)) {
                $contentBlocks[$index]['image'] = $this->saveBase64Image($image, $directory);
            } else {
                // Якщо це вже шлях - запам'ятовуємо що він використовується
                $usedPaths[] = $image;
            }
        }

        // Видаляємо старі зображення, які більше не використовуються
        $pathsToDelete = array_diff($oldImagePaths, $usedPaths);
        foreach ($pathsToDelete as $path) {
            Storage::disk('public')->delete($path);
        }

        return $contentBlocks;
    }

    /**
     * Зберігає Base64 зображення та повертає шлях до файлу
     */
    public function saveBase64Image(string $base64Image, string $directory): string
    {
        // Парсимо base64 дані
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
            $extension = strtolower($matches[1]);
            // Нормалізуємо розширення
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }
            $base64Data = substr($base64Image, strpos($base64Image, ',') + 1);
        } else {
            $extension = 'jpg';
            $base64Data = $base64Image;
        }

        // Декодуємо base64
        $imageData = base64_decode($base64Data);

        if ($imageData === false) {
            throw new \InvalidArgumentException('Invalid Base64 image data');
        }

        // Генеруємо унікальне ім'я файлу
        $filename = $directory.'/'.Str::uuid().'.'.$extension;

        // Зберігаємо файл
        Storage::disk('public')->put($filename, $imageData);

        return $filename;
    }

    /**
     * Витягує шляхи до зображень з content_blocks
     *
     * @param  array<int, array<string, mixed>>|null  $contentBlocks
     * @return array<int, string>
     */
    private function extractImagePaths(?array $contentBlocks): array
    {
        if (empty($contentBlocks)) {
            return [];
        }

        $paths = [];
        foreach ($contentBlocks as $block) {
            if (isset($block['type'], $block['image']) && $block['type'] === 'image') {
                // Перевіряємо що це не Base64
                if (! $this->isBase64Image($block['image'])) {
                    $paths[] = $block['image'];
                }
            }
        }

        return $paths;
    }
}
