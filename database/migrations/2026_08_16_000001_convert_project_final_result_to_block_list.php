<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * final_result історично зберігався як єдиний об'єкт (MyProjectController::uploadFinalResult,
 * save-art) — {type: image|gallery|link, file|files, url}. Filament-панель профілю та
 * art-ua-info API тепер пишуть його як список блоків [{type: gallery|youtube|vimeo|issuu, ...}],
 * і публічні фронтенди (save-art-web, art-ua-info) відображають лише цей новий формат —
 * без конвертації старі записи "зникають" зі сторінки проєкту.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('projects')
            ->select(['id', 'final_result'])
            ->whereNotNull('final_result')
            ->orderBy('id')
            ->chunkById(100, function ($projects): void {
                foreach ($projects as $project) {
                    $converted = $this->convert(json_decode((string) $project->final_result, true));

                    if ($converted === null) {
                        continue;
                    }

                    DB::table('projects')
                        ->where('id', $project->id)
                        ->update(['final_result' => json_encode(
                            $converted,
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                        )]);
                }
            });
    }

    public function down(): void
    {
        // Одностороння нормалізація формату — оригінальний "single object" вигляд
        // (з original_name/mime_type/size/uploaded_at тощо) не відновлюваний з нового формату.
    }

    /**
     * @return list<array<string, mixed>>|null null — уже новий формат, невалідні дані,
     *                                         або тип без відповідника (video/document)
     */
    private function convert(mixed $finalResult): ?array
    {
        if (! is_array($finalResult) || array_is_list($finalResult)) {
            return null;
        }

        return match ($finalResult['type'] ?? null) {
            'image' => $this->imageToGalleryBlock($finalResult),
            'gallery' => $this->galleryToGalleryBlock($finalResult),
            'link' => $this->linkToBlock($finalResult),
            // video/document — це завантажені файли без відповідника серед нових типів
            // (gallery/youtube/vimeo/issuu), лишаємо у старому форматі як є.
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $finalResult
     * @return list<array<string, mixed>>|null
     */
    private function imageToGalleryBlock(array $finalResult): ?array
    {
        $path = $finalResult['file']['path'] ?? null;

        if (! is_string($path) || $path === '') {
            return null;
        }

        return [['type' => 'gallery', 'images' => [$path]]];
    }

    /**
     * @param  array<string, mixed>  $finalResult
     * @return list<array<string, mixed>>|null
     */
    private function galleryToGalleryBlock(array $finalResult): ?array
    {
        $files = $finalResult['files'] ?? null;

        if (! is_array($files)) {
            return null;
        }

        $images = array_values(array_filter(array_map(
            static fn (mixed $file): ?string => is_array($file) ? ($file['path'] ?? null) : null,
            $files,
        )));

        if ($images === []) {
            return null;
        }

        return [['type' => 'gallery', 'images' => $images]];
    }

    /**
     * @param  array<string, mixed>  $finalResult
     * @return list<array<string, mixed>>|null
     */
    private function linkToBlock(array $finalResult): ?array
    {
        $url = $finalResult['url'] ?? null;

        if (! is_string($url) || $url === '') {
            return null;
        }

        $type = match (true) {
            (bool) preg_match('/^https?:\/\/(www\.)?(youtube\.com|youtu\.be)\//i', $url) => 'youtube',
            (bool) preg_match('/^https?:\/\/(www\.)?vimeo\.com\//i', $url) => 'vimeo',
            (bool) preg_match('/^https?:\/\/(www\.)?issuu\.com\//i', $url) => 'issuu',
            default => 'link',
        };

        return [['type' => $type, 'url' => $url]];
    }
};
