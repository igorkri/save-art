<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $parameter_id
 * @property array $value
 * @property int $sort_order
 * @property-read \App\Models\Parameter $parameter
 */
class ParameterValue extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['value'];

    protected $fillable = [
        'parameter_id',
        'value',
        'sort_order',
    ];

    public function getLabel(?string $language = 'uk'): string
    {
        return $this->getTranslation('value', $language) ?: '';
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(Parameter::class);
    }
}
