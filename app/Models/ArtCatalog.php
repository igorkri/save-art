<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ArtCatalogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $image
 * @property int|null $art_category_id
 * @property Carbon|null $published_at
 * @property bool $is_primary
 * @property int $likes_count
 * @property string|null $pdf_file
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read ArtCategory|null $artCategory
 */
class ArtCatalog extends Model
{
    /** @use HasFactory<ArtCatalogFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'image',
        'art_category_id',
        'published_at',
        'is_primary',
        'likes_count',
        'pdf_file',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'date',
            'is_primary' => 'boolean',
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
