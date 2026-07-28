<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property array $title
 * @property string $image
 * @property int|null $art_category_id
 * @property \Carbon\Carbon|null $published_at
 * @property int $likes_count
 * @property string|null $pdf_file
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\ArtCategory|null $artCategory
 */
class ArtCatalog extends Model
{
    /** @use HasFactory<\Database\Factories\ArtCatalogFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'image',
        'art_category_id',
        'published_at',
        'likes_count',
        'pdf_file',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'published_at' => 'date',
            'likes_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function artCategory(): BelongsTo
    {
        return $this->belongsTo(ArtCategory::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ArtCatalogLike::class);
    }
}
