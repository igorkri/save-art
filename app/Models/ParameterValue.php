<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $parameter_id
 * @property string $value
 * @property int $sort_order
 * @property-read \App\Models\Parameter $parameter
 */
class ParameterValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'parameter_id',
        'value',
        'sort_order',
    ];

    public function getLabel(?string $language = 'uk'): string
    {
        return $this->value ?: '';
    }

    public function setValueAttribute(mixed $value): void
    {
        $this->attributes['value'] = is_array($value) ? ($value['uk'] ?? '') : $value;
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(Parameter::class);
    }
}
