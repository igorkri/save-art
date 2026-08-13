<?php

namespace App\Models;

use App\Enums\ParameterType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $art_category_id
 * @property string $name
 * @property ParameterType $type
 * @property int $sort_order
 * @property-read \App\Models\ArtCategory $artCategory
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ParameterValue[] $values
 */
class Parameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'art_category_id',
        'name',
        'type',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => ParameterType::class,
        ];
    }

    public function getLabel(?string $language = 'uk'): string
    {
        return $this->name ?: '';
    }

    public function artCategory(): BelongsTo
    {
        return $this->belongsTo(ArtCategory::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ParameterValue::class)->orderBy('sort_order');
    }
}
