<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $serviceable_type
 * @property int $serviceable_id
 * @property array $title
 * @property string $slug
 * @property array|null $description
 * @property string|null $image
 * @property int|null $art_category_id
 * @property array|null $location
 * @property string|null $price
 * @property Currency|null $currency
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\User|\App\Models\Team $serviceable
 * @property-read \App\Models\ArtCategory|null $artCategory
 */
class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'serviceable_type',
        'serviceable_id',
        'title',
        'slug',
        'description',
        'image',
        'art_category_id',
        'location',
        'price',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'location' => 'array',
            'price' => 'decimal:2',
            'currency' => Currency::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function serviceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function artCategory(): BelongsTo
    {
        return $this->belongsTo(ArtCategory::class);
    }
}
