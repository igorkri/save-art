<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Рендерить першу сторінку PDF-звіту етапу в PNG-мініатюру напряму через
 * Ghostscript (без Imagick) — на проді policy.xml ImageMagick забороняє PDF-кодер
 * (захист від класу вразливостей ImageTragick), тож spatie/pdf-to-image там не
 * запрацював би без ослаблення цієї системної політики.
 *
 * Мініатюра НЕ записується назад у project_stages.documents: це поле
 * повністю перезаписується формою при кожному save() (FileUpload::multiple()
 * заново дегідрує весь масив із плоских шляхів), тож будь-яке поле, дописане
 * джобою окремим update(), губиться при наступному збереженні. Замість цього
 * шлях мініатюри — детермінований: {file}-thumb.png. Споживач (фронтенд/API)
 * сам перевіряє наявність файлу за цим шляхом, без залежності від JSON-поля.
 */
class GenerateStageDocumentPdfThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $path,
        public readonly string $disk = 'public',
    ) {}

    public static function thumbnailPathFor(string $path): string
    {
        return Str::beforeLast($path, '.').'-thumb.png';
    }

    public function handle(): void
    {
        $storage = Storage::disk($this->disk);

        if (! $storage->exists($this->path)) {
            return;
        }

        $thumbnailPath = self::thumbnailPathFor($this->path);
        $sourceFullPath = $storage->path($this->path);
        $thumbnailFullPath = $storage->path($thumbnailPath);

        $result = Process::timeout(60)->run([
            'gs',
            '-sDEVICE=png16m',
            '-o', $thumbnailFullPath,
            '-r150',
            '-dFirstPage=1',
            '-dLastPage=1',
            '-dBATCH',
            '-dNOPAUSE',
            '-dSAFER',
            $sourceFullPath,
        ]);

        if (! $result->successful() || ! file_exists($thumbnailFullPath)) {
            Log::warning('Не вдалося згенерувати мініатюру PDF-звіту етапу', [
                'path' => $this->path,
                'error' => $result->errorOutput(),
            ]);
        }
    }
}
