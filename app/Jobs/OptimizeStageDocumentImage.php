<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Стискає завелике фото звіту етапу проєкту у фоні, щоб не блокувати
 * запит збереження форми. Формат файлу не змінюється (encode() автоматично
 * визначає його з оригіналу), тому шлях/URL, збережені в project_stages.documents,
 * лишаються дійсними.
 */
class OptimizeStageDocumentImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $path,
        public readonly string $disk = 'public',
    ) {}

    public function handle(): void
    {
        if (! Storage::disk($this->disk)->exists($this->path)) {
            return;
        }

        $manager = new ImageManager(new Driver);
        $image = $manager->read(Storage::disk($this->disk)->path($this->path));

        $image->scaleDown(width: 2000, height: 2000);

        Storage::disk($this->disk)->put($this->path, (string) $image->encode());
    }
}
