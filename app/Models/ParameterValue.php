<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $parameter_id
 * @property array<string, string> $value
 * @property int $sort_order
 * @property-read Parameter $parameter
 */
class ParameterValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'parameter_id',
        'value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function getLabel(?string $language = 'uk'): string
    {
        $value = $this->value;

        if (! is_array($value)) {
            return (string) $value;
        }

        return $value[$language] ?? $value['uk'] ?? '';
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(Parameter::class);
    }
}
