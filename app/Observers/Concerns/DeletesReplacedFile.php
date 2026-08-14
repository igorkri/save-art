<?php

namespace App\Observers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait DeletesReplacedFile
{
    /**
     * Видаляє попередній файл із диска, якщо атрибут-шлях змінився під час оновлення моделі.
     */
    protected function deleteReplacedFile(Model $model, string $attribute, string $disk = 'public'): void
    {
        if (! $model->wasChanged($attribute)) {
            return;
        }

        $oldPath = $model->getOriginal($attribute);

        if (blank($oldPath) || Str::startsWith($oldPath, ['http://', 'https://', 'data:image/'])) {
            return;
        }

        if ($oldPath === $model->{$attribute}) {
            return;
        }

        Storage::disk($disk)->delete($oldPath);
    }
}
