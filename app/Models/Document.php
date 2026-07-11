<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property array $name
 * @property DocumentType $type
 * @property array|null $description
 * @property string $file_path
 * @property string|null $file_name
 * @property string|null $file_size
 * @property string|null $mime_type
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Document extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name',
        'type',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'is_active',
    ];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $model) {
            $model->updateFileMetadata();
        });

        static::updated(function (self $model) {
            if ($model->isDirty('file_path')) {
                $model->updateFileMetadata();
            }
        });
    }

    protected function updateFileMetadata(): void
    {
        if (! $this->file_path) {
            return;
        }

        $disk = Storage::disk('public');

        if ($disk->exists($this->file_path)) {
            $this->update([
                'file_name' => basename($this->file_path),
                'file_size' => $disk->size($this->file_path),
                'mime_type' => $disk->mimeType($this->file_path),
            ]);
        }
    }
}
